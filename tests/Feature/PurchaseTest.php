<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Gateway;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    protected User $user;
    protected Product $product;
    protected Gateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = Product::create([
            'name' => 'Test Product',
            'amount' => 1000,
        ]);

        $this->gateway = Gateway::create([
            'name' => 'TestGateway',
            'is_active' => true,
            'priority' => 1,
        ]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'role' => 'USER',
        ]);
    }

    public function test_purchase_with_valid_data()
    {
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'card_number' => '5569000000006063',
            'cvv' => '010'
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'transaction_id',
                'external_id',
                'status',
                'amount',
                'card_last_numbers'
            ]
        ]);
    }

    public function test_purchase_with_invalid_card_number()
    {
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'card_number' => '123',
            'cvv' => '010'
        ]);

        $response->assertStatus(422);
    }

    public function test_purchase_with_invalid_cvv()
    {
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'card_number' => '5569000000006063',
            'cvv' => '12'
        ]);

        $response->assertStatus(422);
    }

    public function test_purchase_with_nonexistent_product()
    {
        $response = $this->postJson('/api/purchase', [
            'product_id' => 999,
            'quantity' => 1,
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'card_number' => '5569000000006063',
            'cvv' => '010'
        ]);

        $response->assertStatus(404);
    }

    public function test_purchase_with_missing_required_fields()
    {
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.name.0', 'The name field is required.');
    }
}
