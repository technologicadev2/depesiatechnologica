<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleSuperadmin = Role::create(['name' => 'superadmin', 'code' => 'superadmin']);
        $roleAdmin = Role::create(['name' => 'admin', 'code' => 'admin']);
        $roleManager = Role::create(['name' => 'manager', 'code' => 'manager']);

        $superadmin = User::create([
            'nom' => 'Super Admin',
            'prenom' => 'Admin',
            'username' => 'superadmin',

            'email' => 'superadmin@technologica.com',
            'password' => bcrypt('superadmin'),
            'role_id' => $roleSuperadmin->id,
            'role_name' => $roleSuperadmin->name,
        ]);

        $admin = User::create([
            'nom' => 'Admin ',
            'prenom' => 'Admin',
            'username' => 'admin',
            
            'email' => 'admin@technologica.com',
            'password' => bcrypt('admin123'),
            'role_id' => $roleAdmin->id,
            'role_name' => $roleAdmin->name,
        ]);

      
    }
}
