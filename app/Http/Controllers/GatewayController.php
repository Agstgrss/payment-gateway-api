<?php

namespace App\Http\Controllers;

use App\Models\Gateway;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    public function toggle($id)
    {
        try {
            $gateway = Gateway::findOrFail($id);
            $gateway->is_active = !$gateway->is_active;
            $gateway->save();

            return response()->json([
                'message' => 'Gateway status updated successfully',
                'data' => $gateway
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Gateway not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error toggling gateway status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function priority(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'priority' => 'required|integer|min:1'
            ]);

            $gateway = Gateway::findOrFail($id);
            $gateway->priority = $validated['priority'];
            $gateway->save();

            return response()->json([
                'message' => 'Gateway priority updated successfully',
                'data' => $gateway
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Gateway not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating gateway priority',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
