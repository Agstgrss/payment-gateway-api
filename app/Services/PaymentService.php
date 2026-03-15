<?php

namespace App\Services;

use App\Models\Gateway;
use App\Models\Client;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionProduct;
use App\Gateways\Gateway1Service;
use App\Gateways\Gateway2Service;

class PaymentService
{
    public function processPayment(array $data)
    {
        // buscar produto
        $product = Product::findOrFail($data['product_id']);

        // calcular valor total
        $amount = $product->amount * $data['quantity'];

        // validar amount
        if ($amount <= 0) {
            throw new \Exception("Invalid amount calculated: " . $amount);
        }

        // buscar ou criar cliente
        $client = Client::firstOrCreate([
            'email' => $data['email']
        ], [
            'name' => $data['name']
        ]);

        // buscar gateways ativos ordenados por prioridade
        $gateways = Gateway::where('is_active', true)
            ->orderBy('priority')
            ->get();

        if ($gateways->isEmpty()) {
            throw new \Exception("No active gateways available");
        }

        $lastError = null;

        foreach ($gateways as $gateway) {
            try {
                $service = $this->resolveGateway($gateway->name);

                $response = $service->createTransaction([
                    'amount' => $amount,
                    'name' => $client->name,
                    'email' => $client->email,
                    'card_number' => $data['card_number'],
                    'cvv' => $data['cvv']
                ]);

                // validar resposta do gateway
                if (!$this->isSuccessfulResponse($response)) {
                    $lastError = $response['error'] ?? 'Gateway returned invalid response';
                    continue;
                }

                // extrair ID da transação da resposta
                $transactionId = $response['id'] ?? $response['transactionId'] ?? null;
                
                if (!$transactionId) {
                    $lastError = 'Gateway did not return transaction ID';
                    continue;
                }

                // criar transação no banco
                $transaction = Transaction::create([
                    'client_id' => $client->id,
                    'gateway_id' => $gateway->id,
                    'external_id' => $transactionId,
                    'status' => 'success',
                    'amount' => $amount,
                    'card_last_numbers' => substr($data['card_number'], -4)
                ]);

                // criar registro de produtos da transação
                TransactionProduct::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'quantity' => $data['quantity']
                ]);

                return $transaction;
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                continue;
            }
        }

        // se chegou aqui, todos os gateways falharam
        throw new \Exception("All gateways failed. Last error: " . ($lastError ?? 'Unknown error'));
    }

    private function isSuccessfulResponse($response): bool
    {
        if (!is_array($response)) {
            return false;
        }

        // Se há erro explícito, é falha
        if (isset($response['error'])) {
            return false;
        }

        // Ambos os gateways retornam um ID em caso de sucesso
        if (isset($response['id']) && !empty($response['id'])) {
            return true;
        }

        // Gateway pode retornar sucesso explícito
        if (isset($response['success']) && $response['success'] === true) {
            return true;
        }

        // Se validou um ID no padrão UUID também é sucesso
        if (isset($response['transactionId']) && !empty($response['transactionId'])) {
            return true;
        }

        // Qualquer outro caso é considerado falha (resposta incompleta/vazia)
        return false;
    }

    private function resolveGateway($name)
    {
        return match ($name) {
            'Gateway1' => new Gateway1Service(),
            'Gateway2' => new Gateway2Service(),
            default => throw new \Exception("Unknown gateway: {$name}")
        };
    }
}