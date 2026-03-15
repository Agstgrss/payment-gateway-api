<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        try {
            $clients = Client::with('transactions')
                ->get();

            return response()->json([
                'data' => $clients
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching clients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $client = Client::with(['transactions' => function ($query) {
                $query->with(['gateway', 'products'])->orderBy('created_at', 'desc');
            }])->findOrFail($id);

            return response()->json([
                'data' => $client
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Client not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching client',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
