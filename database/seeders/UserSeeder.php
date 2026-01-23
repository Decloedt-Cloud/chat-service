<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer des utilisateurs de test
        $users = [
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@example.com',
                'password' => bcrypt('password123'),
            ],
            [
                'name' => 'Bob Smith',
                'email' => 'bob@example.com',
                'password' => bcrypt('password123'),
            ],
            [
                'name' => 'Charlie Brown',
                'email' => 'charlie@example.com',
                'password' => bcrypt('password123'),
            ],
            [
                'name' => 'Diana Prince',
                'email' => 'diana@example.com',
                'password' => bcrypt('password123'),
            ],
            [
                'name' => 'Ethan Hunt',
                'email' => 'ethan@example.com',
                'password' => bcrypt('password123'),
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('✅ Utilisateurs de test créés avec succès !');
    }
}
