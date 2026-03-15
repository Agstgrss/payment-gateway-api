<?php

namespace App\Gateways;

use Illuminate\Support\Facades\Http;

class Gateway1Service implements GatewayInterface
{
    private $baseUrl = 'http://gateways:3001';

    private function getToken()
    {
        try {
            $response = Http::timeout(1800)->post($this->baseUrl . '/login', [
                'email' => 'dev@betalent.tech',
                'token' => 'FEC9BB078BF338F464F96B48089EB498'
            ]);

            if ($response->failed()) {
                throw new \Exception('Gateway 1 login failed: ' . $response->status());
            }

            $data = $response->json();
            
            if (!isset($data['token'])) {
                throw new \Exception('Gateway 1 did not return token');
            }

            return $data['token'];
        } catch (\Exception $e) {
            throw new \Exception('Gateway 1 authentication error: ' . $e->getMessage());
        }
    }

    public function createTransaction(array $data)
    {
        try {
            $token = $this->getToken();

            $response = Http::timeout(1800)->withToken($token)
                ->post($this->baseUrl . '/transactions', [
                    'amount' => $data['amount'],
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'cardNumber' => $data['card_number'],
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
                'error' => 'Gateway 1 connection error: ' . $e->getMessage()
            ];
        }
    }

    public function refund(string $transactionId)
    {
        try {
            $token = $this->getToken();

            $response = Http::timeout(10)->withToken($token)
                ->post($this->baseUrl . "/transactions/{$transactionId}/charge_back");

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
                'error' => 'Gateway 1 refund error: ' . $e->getMessage()
            ];
        }
    }
}