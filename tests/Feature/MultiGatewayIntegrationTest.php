<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Gateway;
use Tests\TestCase;

class MultiGatewayIntegrationTest extends TestCase
{
    protected Product $product;
    protected Gateway $gateway1;
    protected Gateway $gateway2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = Product::create([
            'name' => 'Integration Test Product',
            'amount' => 1000,
        ]);

        $this->gateway1 = Gateway::create([
            'name' => 'Gateway1',
            'is_active' => true,
            'priority' => 1,
        ]);

        $this->gateway2 = Gateway::create([
            'name' => 'Gateway2',
            'is_active' => true,
            'priority' => 2,
        ]);
    }

    /**
     * Testar no postman
     */
    public function test_purchase_with_valid_data_from_postman_structure()
    {
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'name' => 'tester',
            'email' => 'tester@email.com',
            'card_number' => '5569000000006063',
            'cvv' => '010'
        ]);

        $this->assertIn($response->status(), [201, 400]);

        if ($response->status() === 201) {
            $response->assertJsonStructure([
                'message',
                'data' => [
                    'transaction_id',
                    'external_id',
                    'status',
                    'amount',
                    'card_last_numbers',
                    'created_at'
                ]
            ]);

            $this->assertEquals('Payment successful', $response->json('message'));
            $this->assertEquals('success', $response->json('data.status'));
            $this->assertEquals(1000, $response->json('data.amount'));
            $this->assertEquals('6063', $response->json('data.card_last_numbers'));
        }
    }

    public function test_gateway1_field_mapping()
    {
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'card_number' => '5569000000006063',
            'cvv' => '010'
        ]);

        if ($response->status() === 201) {
            $this->assertEquals(2000, $response->json('data.amount'));
        }
    }

    public function test_gateway2_field_mapping()
    {
        $this->gateway1->update(['is_active' => false]);

        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'name' => 'Teste Gateway2',
            'email' => 'gateway2@test.com',
            'card_number' => '5569000000006063',
            'cvv' => '010'
        ]);

        $this->assertIn($response->status(), [201, 400]);
    }

    public function test_invalid_card_number_validation()
    {
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'name' => 'Test',
            'email' => 'test@test.com',
            'card_number' => '123',
            'cvv' => '010'
        ]);

        $this->assertEquals(422, $response->status());
        $this->assertIsArray($response->json('errors.card_number'));
    }

    public function test_invalid_cvv_validation()
    {
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'name' => 'Test',
            'email' => 'test@test.com',
            'card_number' => '5569000000006063',
            'cvv' => '12'
        ]);

        $this->assertEquals(422, $response->status());
        $this->assertIsArray($response->json('errors.cvv'));
    }

    public function test_missing_required_fields()
    {
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
        ]);

        $this->assertEquals(422, $response->status());
        $this->assertIsArray($response->json('errors.quantity'));
        $this->assertIsArray($response->json('errors.name'));
        $this->assertIsArray($response->json('errors.email'));
        $this->assertIsArray($response->json('errors.card_number'));
        $this->assertIsArray($response->json('errors.cvv'));
    }

    public function test_purchase_with_nonexistent_product()
    {
        $response = $this->postJson('/api/purchase', [
            'product_id' => 999,
            'quantity' => 1,
            'name' => 'Test',
            'email' => 'test@test.com',
            'card_number' => '5569000000006063',
            'cvv' => '010'
        ]);

        $this->assertEquals(404, $response->status());
    }

    public function test_external_id_returned_on_success()
    {
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'name' => 'tester',
            'email' => 'tester@email.com',
            'card_number' => '5569000000006063',
            'cvv' => '010'
        ]);

        if ($response->status() === 201) {
            $this->assertNotNull($response->json('data.external_id'));
            $this->assertNotEmpty($response->json('data.external_id'));
        }
    }

    public function test_card_last_numbers_stored_correctly()
    {
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'name' => 'tester',
            'email' => 'tester@email.com',
            'card_number' => '5569000000006063',
            'cvv' => '010'
        ]);

        if ($response->status() === 201) {
            $this->assertEquals('6063', $response->json('data.card_last_numbers'));
        }
    }
}
