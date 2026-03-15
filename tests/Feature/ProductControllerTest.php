<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'role' => 'USER',
        ]);

        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_can_list_products()
    {
        Product::create([
            'name' => 'Product 1',
            'amount' => 1000,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/products');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'amount'
                ]
            ]
        ]);
    }

    public function test_can_create_product()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/products', [
            'name' => 'New Product',
            'amount' => 5000,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'New Product');
    }

    public function test_cannot_create_product_with_invalid_data()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->postJson('/api/products', [
            'name' => '', // Inválido
            'amount' => 'abc', // Inválido
        ]);

        $response->assertStatus(422);
    }

    public function test_can_show_product()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'amount' => 2000,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson("/api/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Test Product');
    }

    public function test_cannot_show_nonexistent_product()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/products/999');

        $response->assertStatus(404);
    }

    public function test_can_update_product()
    {
        $product = Product::create([
            'name' => 'Original Name',
            'amount' => 1000,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->patchJson("/api/products/{$product->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_can_delete_product()
    {
        $product = Product::create([
            'name' => 'Product to Delete',
            'amount' => 1000,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
