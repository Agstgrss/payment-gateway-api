<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Gateways\Gateway1Service;
use App\Gateways\Gateway2Service;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index()
    {
        try {
            $transactions = Transaction::with(['client', 'gateway', 'products'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching transactions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $transaction = Transaction::findOrFail($id);

            return response()->json([
                'data' => $transaction
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Transaction not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function refund($id)
    {
        try {
            $transaction = Transaction::with('gateway')->findOrFail($id);

            if ($transaction->status === 'refunded') {
                return response()->json([
                    'message' => 'This transaction has already been refunded'
                ], 400);
            }

            $gatewayService = $this->resolveGateway($transaction->gateway->name);
            $response = $gatewayService->refund($transaction->external_id);

            if (isset($response['error']) || (isset($response['success']) && !$response['success'])) {
                return response()->json([
                    'message' => 'Refund failed at gateway',
                    'gateway_response' => $response
                ], 400);
            }

            $transaction->status = 'refunded';
            $transaction->save();

            return response()->json([
                'message' => 'Transaction refunded successfully',
                'data' => $transaction
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Transaction not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error processing refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function resolveGateway($name)
    {
        return match ($name) {
            'Gateway1' => new Gateway1Service(),
            'Gateway2' => new Gateway2Service(),
            default => throw new \Exception('Unknown gateway: ' . $name)
        };
    }
}
