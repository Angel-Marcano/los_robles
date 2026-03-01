<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\{Condominium, Tower, Apartment, User, Ownership};

class InitialStructureSeeder extends Seeder
{
    public function run(): void
    {
        $condo = Condominium::firstOrCreate(['name' => 'Los Robles']);

        $towers = ['Torre A', 'Torre B', 'Torre C'];

        foreach ($towers as $tName) {
            $prefix = match($tName) {
                'Torre A' => 'A',
                'Torre B' => 'B',
                default   => 'C',
            };

            $tower = Tower::firstOrCreate([
                'name' => $tName,
                'condominium_id' => $condo->id,
            ]);

            for ($i = 1; $i <= 4; $i++) {
                $code = $prefix . $i;
                $apartment = Apartment::firstOrCreate([
                    'tower_id' => $tower->id,
                    'code'     => $code,
                ]);

                // Crear usuario propietario para cada apartamento
                $email = strtolower($code) . '@user.com';
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name'     => 'Propietario ' . $code,
                        'password' => Hash::make('1234'),
                        'active'   => true,
                    ]
                );

                // Asignar rol owner si Spatie está disponible
                if (!$user->hasRole('owner')) {
                    $user->assignRole('owner');
                }

                // Vincular ownership
                Ownership::firstOrCreate([
                    'apartment_id' => $apartment->id,
                    'user_id'      => $user->id,
                ], [
                    'role'   => 'owner',
                    'active' => true,
                ]);
            }
        }
    }
}
