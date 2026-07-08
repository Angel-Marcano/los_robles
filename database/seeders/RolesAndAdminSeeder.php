<?php
namespace Database\Seeders; 
use Illuminate\Database\Seeder; 
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Str;
use App\Models\User; 
use Spatie\Permission\Models\Role;
class RolesAndAdminSeeder extends Seeder { 
    public function run(): void { 
        $roles = ['super_admin', 'condo_admin', 'tower_admin', 'owner', 'co_owner', 'tenant']; 
        foreach ($roles as $r) { 
            Role::firstOrCreate(['name' => $r]); 
        } 
        $password = env('SEED_ADMIN_PASSWORD') ?: Str::random(16);
        $admin = User::firstOrCreate(['email' => 'admin@admin.com'], ['name' => 'Super Admin', 'password' => Hash::make($password)]); 
        $admin->assignRole('super_admin'); 
        if ($admin->wasRecentlyCreated && !env('SEED_ADMIN_PASSWORD') && $this->command) {
            $this->command->warn('Contraseña generada para admin@admin.com: '.$password.' (guárdala; no se volverá a mostrar)');
        }
    }
}
