<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class WalletController extends Controller
{
    /**
     * Add funds to the wallet and initiate STK push.
     */
    public function addFunds(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'amount' => 'required|numeric|min:0.01', // Amount should be greater than 0
            'phone' => 'required|string|min:10', // Ensure phone number is provided and has minimum length
        ]);

        try {
            // Get the user ID from the authenticated user
            $userId = Auth::user()->id;

            // Find or create the wallet for the authenticated user
            $wallet = Wallet::firstOrCreate(['user_id' => $userId]);

            // Add balance to the wallet
            $wallet->addBalance($request->amount);

            // Call the STK Push API
            $response = $this->initiateStkPush($request->phone, $request->amount, $userId);

            // Check if the STK push was successful
            if ($response['status'] == 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Funds added successfully and STK push initiated!',
                    'balance' => $wallet->amount,
                ]);
            } else {
                // If STK push fails, rollback the wallet balance
                $wallet->removeBalance($request->amount);
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Failed to initiate STK push. Funds not added.',
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to add funds: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Call the STK Push API.
     */
    private function initiateStkPush($phone, $amount ,$userId)
    {
        try {
            $url = 'https://payment.cityrealtykenya.com/api/mpesa/stk/initiate';
            
            // Prepare the request payload
            $data = [
                'msisdn' => $phone,
                'amount' => $amount,
                'userId' =>$userId
                // Add any other necessary parameters (e.g., short code, token, etc.)
            ];

            // Make the POST request to the STK API
            $response = Http::post($url, $data);

            // Handle response, checking for success
            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'message' => 'STK Push initiated successfully.',
                    'data' => $response->json(),
                ];
            } else {
                return [
                    'status' => 'failed',
                    'message' => 'STK Push initiation failed.',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'message' => 'Error during STK Push request: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Remove funds from the wallet.
     */
    public function removeFunds(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'amount' => 'required|numeric|min:0.01', // Ensure the amount is positive
        ]);

        try {
            // Get the user ID from the authenticated user
            $userId = Auth::user()->id;

            // Find the wallet for the authenticated user
            $wallet = Wallet::where('user_id', $userId)->first();

            if (!$wallet) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Wallet not found for this user.',
                ], 404);
            }

            // Remove balance from the wallet
            $wallet->removeBalance($request->amount);

            return response()->json([
                'status' => 'success',
                'message' => 'Funds removed successfully!',
                'balance' => $wallet->amount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to remove funds: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the current balance of the wallet.
     */
    public function getBalance()
    {
        try {
            // Get the user ID from the authenticated user
            $userId = Auth::user()->id;

            // Find the wallet for the authenticated user
            $wallet = Wallet::where('user_id', $userId)->first();

            if (!$wallet) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Wallet not found for this user.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'balance' => $wallet->amount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to fetch balance: ' . $e->getMessage(),
            ], 500);
        }
    }
}
