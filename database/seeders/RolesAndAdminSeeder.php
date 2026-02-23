<?php
namespace Database\Seeders; 
use Illuminate\Database\Seeder; 
use Illuminate\Support\Facades\Hash; 
use App\Models\User; 
use Spatie\Permission\Models\Role;
class RolesAndAdminSeeder extends Seeder { 
    public function run(): void { 
        $roles = ['super_admin', 'condo_admin', 'tower_admin', 'owner', 'co_owner', 'tenant']; 
        foreach ($roles as $r) { 
            Role::firstOrCreate(['name' => $r]); 
        } 
        $admin = User::firstOrCreate(['email' => 'admin@admin.com'], ['name' => 'Super Admin', 'password' => Hash::make('12345678')]); 
        $admin->assignRole('super_admin'); 
    }
}
