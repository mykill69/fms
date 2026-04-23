<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Super Admin - Full access
        User::create([
            'name' => 'Michael Balivia',
            'email' => 'michaelbalivia@cpsu.edu.ph',
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'access_permissions' => null, // null means full access
            'status' => 'active'
        ]);

    }
}