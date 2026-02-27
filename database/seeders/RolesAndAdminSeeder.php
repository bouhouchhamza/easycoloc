<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;


class RolesAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $adminRole = Role::firstOrCreate(['name'=>'admin']);
        Role::firstOrCreate(['name'=>'member']);

        $admin = User::firstOrCreate(['email' => 'admin@easycoloc.test']
        ,[
            'name'=>'EastColoc Admin',
            'password' =>Hash::make('password123'),
        ]);
        if(!$admin->hasRole($adminRole->name)){
            $admin->assignRole($adminRole);
        }
    }
}
