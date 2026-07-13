<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Création du compte super-administrateur
        User::updateOrCreate(
            ['email' => 'admin@asddaniel.com'], // Remplace par ton email
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'), // Remplace par un mot de passe fort
            ]
        );
    }
}
