<?php

namespace App\Gateways;

use Illuminate\Support\Facades\Http;

class Gateway2Service implements GatewayInterface
{
    private $baseUrl = 'http://gateways:3002';

    private function getAuthHeaders()
    {
        return [
            'Gateway-Auth-Token' => 'tk_f2198cc671b5289fa856',
            'Gateway-Auth-Secret' => '3d15e8ed6131446ea7e3456728b1211f'
        ];
    }

    public function createTransaction(array $data)
    {
        try {
            $response = Http::timeout(10)->withHeaders($this->getAuthHeaders())
                ->post($this->baseUrl . '/transacoes', [
                    'valor' => $data['amount'],
                    'nome' => $data['name'],
                    'email' => $data['email'],
                    'numeroCartao' => $data['card_number'],
                    'cvv' => $data['cvv']
                ]);

            if ($response->failed()) {
                return [
                    'error' => 'Transaction failed',
                    'status' => $response->status(),
                    'details' => $response->json() ?? $response->body()
                ];
            }

            return $response->json();
        } catch (\Exception $e) {
            return [
                'error' => 'Gateway 2 connection error: ' . $e->getMessage()
            ];
        }
    }

    public function refund(string $transactionId)
    {
        try {
            $response = Http::timeout(10)->withHeaders($this->getAuthHeaders())
                ->post($this->baseUrl . '/transacoes/reembolso', [
                    'id' => $transactionId
                ]);

            if ($response->failed()) {
                return [
                    'error' => 'Refund failed',
                    'status' => $response->status(),
                    'details' => $response->json() ?? $response->body()
                ];
            }

            return $response->json();
        } catch (\Exception $e) {
            return [
                'error' => 'Gateway 2 refund error: ' . $e->getMessage()
            ];
        }
    }
}