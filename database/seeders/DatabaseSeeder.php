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
     * Seed the application's database.
     */
    public function run(): void
    {
        // Criar usuário de teste
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'USER',
        ]);

        // Criar usuário admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'ADMIN',
        ]);

        // Criar gateways
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

        // Criar produtos
        Product::create([
            'name' => 'Produto Premium',
            'amount' => 5000, // 50.00 em reais (em centavos)
        ]);

        Product::create([
            'name' => 'Produto Standard',
            'amount' => 2000, // 20.00 em reais
        ]);

        Product::create([
            'name' => 'Produto Básico',
            'amount' => 1000, // 10.00 em reais
        ]);
    }
}

