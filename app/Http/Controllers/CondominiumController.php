<?php
namespace App\Http\Controllers;

use App\Models\Condominium;
use App\Models\Tenant\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CondominiumController extends Controller {

    public function index(){
        $items = Condominium::paginate(20);
        return view('condominiums.index', compact('items'));
    }

    public function create(){
        return view('condominiums.create');
    }

    public function store(Request $r){
        $data = $r->validate([
            'name'           => 'required|string|max:120',
            'subdomain'      => 'required|string|max:60|unique:condominiums,subdomain',
            'admin_email'    => 'required|email',
            'admin_password' => 'required|string|min:6',
        ]);

        // El subdomain puede ser un subdominio (demo) o un dominio completo (laspalmas.com)
        // Para la BD usamos solo la primera parte sin puntos
        $subdomain = strtolower(trim($data['subdomain']));
        $parts = explode('.', $subdomain);
        $dbSlug = preg_replace('/[^a-z0-9]/', '_', $parts[0]);
        $dbName = 'db_' . $dbSlug;

        // Crear BD física
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Registrar condominio
        $condominium = Condominium::create([
            'name'      => $data['name'],
            'subdomain' => $subdomain,
            'db_name'   => $dbName,
            'active'    => true,
        ]);

        // Configurar conexión tenant y migrar
        $base = config('database.connections.mysql');
        config(['database.connections.tenant' => array_merge($base, ['database' => $dbName])]);
        DB::purge('tenant');

        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path'     => 'database/migrations/tenant',
            '--force'    => true,
        ]);

        // Sembrar roles
        Artisan::call('db:seed', [
            '--class'  => 'Database\\Seeders\\TenantRolesSeeder',
            '--force'  => true,
        ]);

        // Crear usuario admin
        app()->instance('currentCondominium', $condominium);
        $user = \App\Models\User::on('tenant')->create([
            'name'                 => 'Administrador ' . $data['name'],
            'first_name'           => 'Administrador',
            'last_name'            => $data['name'],
            'document_type'        => 'cedula',
            'document_number'      => 'V-00000000',
            'email'                => $data['admin_email'],
            'password'             => bcrypt($data['admin_password']),
            'active'               => true,
            'accepted_privacy_at'  => now(),
            'accepted_terms_at'    => now(),
            'legal_version'        => config('app.legal_version', '1.0'),
        ]);

        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user->assignRole($role);

        return redirect()->route('condominiums.index')
            ->with('status', "Condominio '{$data['name']}' creado. Accede por http://{$subdomain}.test con {$data['admin_email']}");
    }

    public function show(Condominium $condominium){
        return view('condominiums.show', compact('condominium'));
    }

    public function edit(Condominium $condominium){
        return view('condominiums.edit', compact('condominium'));
    }

    public function update(Request $r, Condominium $condominium){
        $data = $r->validate([
            'name'      => 'required|string|max:120',
            'active'    => 'sometimes|boolean',
            'subdomain' => 'sometimes|string|max:60|unique:condominiums,subdomain,' . $condominium->id,
        ]);
        $condominium->update($data);
        return redirect()->route('condominiums.index');
    }

    public function destroy(Condominium $condominium){
        $condominium->delete();
        return redirect()->route('condominiums.index');
    }
}
