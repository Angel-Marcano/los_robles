<?php

namespace App\Console\Commands;

use App\Models\{Apartment, Condominium, Ownership, Tower, User};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeedTenantStructure extends Command
{
    protected $signature = 'tenants:seed-structure
        {subdomain : Subdominio del condominio}
        {--towers=A,B,C : Lista de torres (ej: A,B,C)}
        {--domain=user.com : Dominio para emails automáticos}
        {--password=12345678 : Clave para usuarios automáticos}
        {--dry-run : Solo muestra lo que haría, sin escribir en BD}';

    protected $description = 'Carga masiva de torres/apartamentos/usuarios para un tenant.';

    public function handle(): int
    {
        $subdomain = strtolower(trim((string) $this->argument('subdomain')));
        $towerCsv = (string) $this->option('towers');
        $emailDomain = trim((string) $this->option('domain'));
        $password = (string) $this->option('password');
        $dryRun = (bool) $this->option('dry-run');

        if ($subdomain === '') {
            $this->error('Subdominio inválido.');
            return Command::FAILURE;
        }

        if ($emailDomain === '' || strpos($emailDomain, '.') === false) {
            $this->error('Dominio de email inválido. Ej: user.com');
            return Command::FAILURE;
        }

        $condo = Condominium::where('subdomain', $subdomain)->first();
        if (!$condo) {
            $this->error("No existe un condominio con subdomain '{$subdomain}'.");
            return Command::FAILURE;
        }
        if (!$condo->active) {
            $this->warn('El condominio está inactivo; igual se intentará ejecutar.');
        }
        if (!$condo->db_name) {
            $this->error('El condominio no tiene db_name configurado.');
            return Command::FAILURE;
        }

        // Configurar conexión tenant igual que en tenants:create
        $base = config('database.connections.mysql');
        $tenantConfig = array_merge($base, ['database' => $condo->db_name]);
        config(['database.connections.tenant' => $tenantConfig]);
        DB::purge('tenant');
        try {
            DB::connection('tenant')->getPdo();
        } catch (\Throwable $e) {
            $this->error('No se pudo conectar a la BD tenant: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Asegurar que los modelos tenant usen la conexión tenant (trait UsesTenantConnection)
        app()->instance('currentCondominium', $condo);

        $towerCodes = collect(explode(',', $towerCsv))
            ->map(fn ($v) => strtoupper(trim((string) $v)))
            ->filter()
            ->values();

        if ($towerCodes->isEmpty()) {
            $this->error('No se especificaron torres. Usa --towers=A,B,C');
            return Command::FAILURE;
        }

        $suffixes = $this->apartmentSuffixes();

        $this->info('Tenant: ' . $condo->name . ' (' . $condo->subdomain . ') DB=' . $condo->db_name);
        $this->info('Torres: ' . $towerCodes->implode(', '));
        $this->info('Apartamentos por torre: ' . count($suffixes));
        $this->info('Modo: ' . ($dryRun ? 'DRY-RUN (sin escribir)' : 'ESCRIBIENDO'));

        $createdTowers = 0;
        $createdApts = 0;
        $updatedApts = 0;
        $createdUsers = 0;
        $createdOwnerships = 0;

        foreach ($towerCodes as $towerCode) {
            $towerName = 'Torre ' . $towerCode;

            $tower = null;
            if (!$dryRun) {
                $tower = Tower::on('tenant')->firstOrCreate(
                    ['name' => $towerName],
                    ['active' => true]
                );
                if ($tower->wasRecentlyCreated) {
                    $createdTowers++;
                }
            }

            $sumAliquotScaled = 0;

            foreach ($suffixes as $suffix) {
                $aliquot = $this->aliquotForSuffix($suffix);
                $sumAliquotScaled += $this->scaleAliquot($aliquot);

                $aptCode = $towerCode . '-' . $suffix;
                $username = 'user_' . $towerCode . '_' . $suffix;
                $email = $username . '@' . $emailDomain;

                if ($dryRun) {
                    continue;
                }

                $apartment = Apartment::on('tenant')->where('code', $aptCode)->first();
                if (!$apartment) {
                    $apartment = Apartment::on('tenant')->create([
                        'tower_id' => $tower->id,
                        'code' => $aptCode,
                        'aliquot_percent' => $aliquot,
                        'active' => true,
                    ]);
                    $createdApts++;
                } else {
                    $apartment->update([
                        'tower_id' => $tower->id,
                        'aliquot_percent' => $aliquot,
                        'active' => true,
                    ]);
                    $updatedApts++;
                }

                $user = User::on('tenant')->where('email', $email)->first();
                if (!$user) {
                    $user = User::on('tenant')->create([
                        'name' => $username,
                        'email' => $email,
                        'password' => Hash::make($password),
                        'active' => true,
                    ]);
                    $createdUsers++;
                } else {
                    // mantener password existente; solo asegurar activo y nombre consistente
                    $user->update([
                        'name' => $user->name ?: $username,
                        'active' => true,
                    ]);
                }

                $ownership = Ownership::on('tenant')->firstOrCreate(
                    ['apartment_id' => $apartment->id, 'user_id' => $user->id],
                    ['role' => 'owner', 'active' => true]
                );
                if ($ownership->wasRecentlyCreated) {
                    $createdOwnerships++;
                } else {
                    $ownership->update(['role' => $ownership->role ?: 'owner', 'active' => true]);
                }
            }

            $this->line("Suma alícuotas {$towerName}: " . $this->formatScaledAliquot($sumAliquotScaled));
        }

        if ($dryRun) {
            $this->info('DRY-RUN completado. (No se escribieron cambios)');
            return Command::SUCCESS;
        }

        $this->info('Listo.');
        $this->line('Torres creadas: ' . $createdTowers);
        $this->line('Apartamentos creados: ' . $createdApts . ' | actualizados: ' . $updatedApts);
        $this->line('Usuarios creados: ' . $createdUsers);
        $this->line('Ownerships creados: ' . $createdOwnerships);

        return Command::SUCCESS;
    }

    /**
     * @return array<int,string>
     */
    private function apartmentSuffixes(): array
    {
        $out = ['01', '02', '03', '04'];
        for ($floor = 1; $floor <= 4; $floor++) {
            for ($apt = 1; $apt <= 4; $apt++) {
                $out[] = (string) ($floor . $apt);
            }
        }
        for ($apt = 1; $apt <= 4; $apt++) {
            $out[] = '5' . $apt;
        }
        // unique + stable order
        $out = array_values(array_unique($out));
        return $out;
    }

    private function aliquotForSuffix(string $suffix): string
    {
        $suffix = trim($suffix);

        if (in_array($suffix, ['01', '02', '04'], true)) {
            return '4.40623320';
        }
        if ($suffix === '03') {
            return '3.38527670';
        }

        $n = (int) $suffix;
        if ($n >= 11 && $n <= 44) {
            return '4.08382590';
        }
        if ($n >= 51 && $n <= 54) {
            return '4.51370230';
        }

        return '0.00000000';
    }

    private function scaleAliquot(string $aliquot): int
    {
        $aliquot = trim($aliquot);
        if ($aliquot === '') {
            return 0;
        }

        if (!str_contains($aliquot, '.')) {
            return (int) ($aliquot . '00000000');
        }

        [$intPart, $decPart] = explode('.', $aliquot, 2);
        $intPart = $intPart === '' ? '0' : $intPart;
        $decPart = str_pad(substr($decPart, 0, 8), 8, '0');

        return (int) ($intPart . $decPart);
    }

    private function formatScaledAliquot(int $scaled): string
    {
        $base = 100000000;
        $intPart = intdiv($scaled, $base);
        $decPart = (string) ($scaled % $base);
        $decPart = str_pad($decPart, 8, '0', STR_PAD_LEFT);
        return $intPart . '.' . $decPart;
    }
}
