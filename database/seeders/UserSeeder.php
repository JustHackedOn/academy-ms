<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Owner (Admin)
        User::updateOrCreate(
            ['email' => 'admin@academy.pk'],
            [
                'name' => 'Academy Owner',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2. Partner
        User::updateOrCreate(
            ['email' => 'partner@academy.pk'],
            [
                'name' => 'Academy Partner',
                'password' => Hash::make('password'),
                'role' => 'partner',
                'email_verified_at' => now(),
            ]
        );
    }
}
