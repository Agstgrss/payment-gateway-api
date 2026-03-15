<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Gateway;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed para o banco
     */
    public function run(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'USER',
        ]);

        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'ADMIN',
        ]);

        Gateway::create([
            'name' => 'Gateway1',
            'is_active' => true,
            'priority' => 1,
        ]);

        Gateway::create([
            'name' => 'Gateway2',
            'is_active' => true,
            'priority' => 2,
        ]);

        Product::create([
            'name' => 'Produto Premium',
            'amount' => 5111,
        ]);

        Product::create([
            'name' => 'Produto normal',
            'amount' => 2000,
        ]);

        Product::create([
            'name' => 'Produto Basico',
            'amount' => 1000,
        ]);
    }
}

