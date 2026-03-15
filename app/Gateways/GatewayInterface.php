<?php

namespace App\Gateways;

interface GatewayInterface
{
    public function createTransaction(array $data);

    public function refund(string $transactionId);
}