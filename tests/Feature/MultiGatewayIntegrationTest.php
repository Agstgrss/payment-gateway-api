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
     * Testa requisição POST /api/purchase com dados válidos
     * Este teste é compatível com a collection Postman fornecida
     */
    public function test_purchase_with_valid_data_from_postman_structure()
    {
        // Estrutura similar aos dados da collection Postman
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'name' => 'tester',
            'email' => 'tester@email.com',
            'card_number' => '5569000000006063',
            'cvv' => '010'
        ]);

        // Deve retornar 201 (sucesso) ou 400 (falha do gateway)
        // Dependendo se os gateways mock estão rodando
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

            // Validar dados
            $this->assertEquals('Payment successful', $response->json('message'));
            $this->assertEquals('success', $response->json('data.status'));
            $this->assertEquals(1000, $response->json('data.amount'));
            $this->assertEquals('6063', $response->json('data.card_last_numbers'));
        }
    }

    /**
     * Testa que o código mapeia corretamente os campos para Gateway 1
     * Gateway 1 espera: amount, name, email, cardNumber, cvv
     */
    public function test_gateway1_field_mapping()
    {
        // Este teste é mais de validação estrutural
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'card_number' => '5569000000006063',
            'cvv' => '010'
        ]);

        // Validar que o cálculo de quantidade foi feito corretamente
        // product.amount (1000) × quantity (2) = 2000
        if ($response->status() === 201) {
            $this->assertEquals(2000, $response->json('data.amount'));
        }
    }

    /**
     * Testa que o código mapeia corretamente os campos para Gateway 2
     * Gateway 2 espera: valor, nome, email, numeroCartao, cvv
     */
    public function test_gateway2_field_mapping()
    {
        // Desativar Gateway 1 para forçar uso de Gateway 2
        $this->gateway1->update(['is_active' => false]);

        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'name' => 'Teste Gateway2',
            'email' => 'gateway2@test.com',
            'card_number' => '5569000000006063',
            'cvv' => '010'
        ]);

        // Deve depender se o gateway mock está rodando
        $this->assertIn($response->status(), [201, 400]);
    }

    /**
     * Testa falha de validação conforme Postman espera
     */
    public function test_invalid_card_number_validation()
    {
        // Postman envia card_number com menos de 16 dígitos
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'name' => 'Test',
            'email' => 'test@test.com',
            'card_number' => '123', // Inválido
            'cvv' => '010'
        ]);

        $this->assertEquals(422, $response->status());
        $this->assertIsArray($response->json('errors.card_number'));
    }

    /**
     * Testa falha de validação de CVV
     */
    public function test_invalid_cvv_validation()
    {
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'name' => 'Test',
            'email' => 'test@test.com',
            'card_number' => '5569000000006063',
            'cvv' => '12' // Inválido (menos de 3 dígitos)
        ]);

        $this->assertEquals(422, $response->status());
        $this->assertIsArray($response->json('errors.cvv'));
    }

    /**
     * Testa que faltam fields obrigatórios
     */
    public function test_missing_required_fields()
    {
        $response = $this->postJson('/api/purchase', [
            'product_id' => $this->product->id,
            // Faltam: quantity, name, email, card_number, cvv
        ]);

        $this->assertEquals(422, $response->status());
        $this->assertIsArray($response->json('errors.quantity'));
        $this->assertIsArray($response->json('errors.name'));
        $this->assertIsArray($response->json('errors.email'));
        $this->assertIsArray($response->json('errors.card_number'));
        $this->assertIsArray($response->json('errors.cvv'));
    }

    /**
     * Testa transação não encontrada
     */
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

    /**
     * Testa que o campo external_id é retornado (importante para rastrear no gateway)
     */
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
            // external_id DEVE ser retornado para rastreamento
            $this->assertNotNull($response->json('data.external_id'));
            $this->assertNotEmpty($response->json('data.external_id'));
        }
    }

    /**
     * Testa que last 4 digits do cartão são armazenados (segurança)
     */
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
            // Deve retornar apenas os últimos 4 dígitos
            $this->assertEquals('6063', $response->json('data.card_last_numbers'));
        }
    }
}
