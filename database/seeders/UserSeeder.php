<?php

namespace Database\Seeders;

use App\Models\Note;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'alaa',
                'email' => 'alaa.gbh0@gmail.com',
                'username' => 'alaa',
                'password' => Hash::make('admin1234'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'ahmed khalid',
                'email' => 'ahmed.khalid@example.com',
                'username' => 'ahmed_khalid',
                'password' => Hash::make('admin1234'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'sara mohamed',
                'email' => 'sara.mohamed@example.com',
                'username' => 'sara_mohamed',
                'password' => Hash::make('admin1234'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'omar hassan',
                'email' => 'omar.hassan@example.com',
                'username' => 'omar_hassan',
                'password' => Hash::make('admin1234'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'layla abbas',
                'email' => 'layla.abbas@example.com',
                'username' => 'layla_abbas',
                'password' => Hash::make('admin1234'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'youssef ali',
                'email' => 'youssef.ali@example.com',
                'username' => 'youssef_ali',
                'password' => Hash::make('admin1234'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'nour mahmoud',
                'email' => 'nour.mahmoud@example.com',
                'username' => 'nour_mahmoud',
                'password' => Hash::make('admin1234'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'kareem adel',
                'email' => 'kareem.adel@example.com',
                'username' => 'kareem_adel',
                'password' => Hash::make('admin1234'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'fatima zahra',
                'email' => 'fatima.zahra@example.com',
                'username' => 'fatima_zahra',
                'password' => Hash::make('admin1234'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'tariq samir',
                'email' => 'tariq.samir@example.com',
                'username' => 'tariq_samir',
                'password' => Hash::make('admin1234'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            Profile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id' => $user->id,
                    'language' => 'en',
                    'theme' => 'dark',
                    'is_public' => true,
                    'allow_messages' => true,
                    'allow_invitation_requests' => true,
                ]
            );

            Note::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id' => $user->id,
                    'title' => 'My Note',
                    'color' => '#1b1919',
                ]
            );
        }
    }
}
