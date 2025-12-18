<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test users with initial balances
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'balance' => '100000.00000000', // $100,000
            ],
            [
                'name' => 'Alice Trader',
                'email' => 'alice@example.com',
                'password' => Hash::make('password'),
                'balance' => '50000.00000000', // $50,000
            ],
            [
                'name' => 'Bob Investor',
                'email' => 'bob@example.com',
                'password' => Hash::make('password'),
                'balance' => '75000.00000000', // $75,000
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create($userData);

            // Give each user some initial crypto assets
            Asset::create([
                'user_id' => $user->id,
                'symbol' => 'BTC',
                'amount' => '1.00000000',
                'locked_amount' => '0.00000000',
            ]);

            Asset::create([
                'user_id' => $user->id,
                'symbol' => 'ETH',
                'amount' => '10.00000000',
                'locked_amount' => '0.00000000',
            ]);

            Asset::create([
                'user_id' => $user->id,
                'symbol' => 'SOL',
                'amount' => '100.00000000',
                'locked_amount' => '0.00000000',
            ]);
        }

        $this->command->info('Test users created with initial balances and assets!');
        $this->command->info('Login credentials:');
        $this->command->info('Email: admin@example.com | Password: password');
        $this->command->info('Email: alice@example.com | Password: password');
        $this->command->info('Email: bob@example.com | Password: password');
    }
}