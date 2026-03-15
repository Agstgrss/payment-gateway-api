<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaymentService;

class PurchaseController extends Controller
{
    private $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function purchase(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'card_number' => 'required|digits:16',
                'cvv' => 'required|digits:3'
            ]);

            $transaction = $this->paymentService->processPayment($validated);

            return response()->json([
                'message' => 'Payment successful',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'external_id' => $transaction->external_id,
                    'status' => $transaction->status,
                    'amount' => $transaction->amount,
                    'card_last_numbers' => $transaction->card_last_numbers,
                    'created_at' => $transaction->created_at
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Payment failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}