<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'User A', 'email' => 'usera@wallet.com', 'balance' => 100000],
            ['name' => 'User B', 'email' => 'userb@wallet.com', 'balance' => 100000],
            ['name' => 'User C', 'email' => 'userc@wallet.com', 'balance' => 100000],
        ];

        foreach ($users as $user) {
            \App\Models\User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => \Illuminate\Support\Facades\Hash::make('password123'),
                'balance' => $user['balance']
            ]);
        }
    }
}
