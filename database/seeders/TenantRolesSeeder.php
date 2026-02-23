<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TenantRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Roles base para operar dentro de un tenant
        $roles = [
            'super_admin',    // Acceso total dentro del tenant
            'condo_admin',    // Administración general del condominio
            'tower_admin',    // Administración limitada a una torre
            'owner',          // Propietario principal
            'co_owner',       // Copropietario
            'tenant',         // Inquilino (arrendatario)
        ];
        foreach ($roles as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
