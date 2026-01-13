<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Mesin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // TODO: Add other roles here
        Role::findOrCreate('super_admin');
        Role::findOrCreate('deskprint');
        Role::findOrCreate('kasir');
        Role::findOrCreate('operator');
        Role::findOrCreate('ppic');
        Role::findOrCreate('customer');

        // TODO: Add other users here

        $superAdmin = User::query()
            ->firstOrCreate([
                'name' => 'Super Admin',
                'email' => 'superadmin@gmail.com',
                'is_active' => true,
            ], [
                'password' => Hash::make('password'),
            ]);

        // TODO: Add role assignments here
        $superAdmin->assignRole(Role::all());

        // Assign all mesins to superadmin
        $superAdmin->mesins()->sync(Mesin::pluck('id')->toArray());
    }
}
