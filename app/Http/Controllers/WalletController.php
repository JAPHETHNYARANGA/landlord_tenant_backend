<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Jobs\CheckTransactionStatus; // Add the use statement at the top
use App\Jobs\CheckWalletRechargeStatus;
use App\Models\RentPayments;
use Illuminate\Support\Facades\Log;

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
            $user = Auth::user();
            $userId = $user->user_id;
            $payment_user_id = $user->id;

            // Find or create the wallet for the authenticated user
            $wallet = Wallet::firstOrCreate(['user_id' => $userId]);

            // Call the STK Push API to initiate the payment
            $response = $this->initiateStkPush($request->phone, $request->amount, $payment_user_id);

            // Check if the STK push was initiated successfully
            if ($response['status'] == 'success') {
                // Get the bill reference from the response
                $billRef = $response['data']['account_reference'];

                // Dispatch the job to check transaction status in the background
                // CheckTransactionStatus::dispatch($billRef, $userId, $request->amount);
                CheckWalletRechargeStatus::dispatch($billRef, $userId, $request->amount);

                return response()->json([
                    'status' => 'success',
                    'message' => 'STK Push initiated successfully. Transaction status will be updated shortly.',
                ]);
            } else {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Failed to initiate STK push.',
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
     * Check the transaction status from M-PESA using the transaction ID.
     */
    public function checkTransactionStatus($transactionId)
    {
        // This method should interact with M-PESA to get the status of the transaction
        // You can use the MpesaDataFetchController's fetchCustomerTransaction or a similar method

        try {
            // Call your endpoint or method that fetches transaction status
            $response = Http::post('http://51.83.128.210:8082/api/mpesa/confirmTransactions', [
                'transaction_id' => $transactionId,
            ]);

            // Check if the response is successful and contains the correct status
            if ($response->successful()) {
                // Check if the response status is 'success' as expected
                if ($response['status'] == 'success') {
                    return 'success'; // Transaction was successful
                } else {
                    // Handle different status cases if necessary
                    return 'failed'; // Transaction failed
                }
            } else {
                // Log unsuccessful response for debugging purposes
                // Log::error("Failed to get transaction status for ID: $transactionId", ['response' => $response->body()]);
                return 'failed'; // If the response is not successful
            }
        } catch (\Exception $e) {
            // Log the exception for further analysis
            // Log::error("Error checking transaction status for ID: $transactionId", ['error' => $e->getMessage()]);
            return 'failed'; // Return failed if there was an exception
        }
    }


    /**
     * Call the STK Push API.
     */
    private function initiateStkPush($phone, $amount, $userId)
    {
        try {
            $url = 'http://51.83.128.210:8082/api/mpesa/stk/initiate';

            // Prepare the request payload
            $data = [
                'msisdn' => $phone,
                'amount' => $amount,
                'userId' => $userId
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

            $user = Auth::user();

            $userId = $user->user_id;

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
            $user = Auth::user();

            $userId = $user->user_id;

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

    public function payRent(Request $request)
    {
        try {
            $user = Auth::user();
            $userId = $user->user_id;
            $payment_user_id = $user->id;

            // Validate the incoming request
            $request->validate([
                'amount' => 'required|numeric|min:0.01', // Ensure the amount is positive
                'phone' => 'nullable',
                'paymentChannel' => 'required',
            ]);

            $paymentChannel = $request->paymentChannel;
            $amount = $request->amount;
            $serviceFee = $amount * 0.075; // 7.5% service fee
            $landlordAmount = $amount - $serviceFee; // Amount to credit landlord

            // Find the tenant
            $tenant = Tenant::where('user_id', $userId)->first();

            if (!$tenant) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Tenant not found.',
                ], 404);
            }

            // Find the landlord associated with this tenant's property
            $landlord = $tenant->property->landlord;

            if (!$landlord) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Landlord not found for this tenant.',
                ], 404);
            }

            // Find the landlord's wallet
            $landlordWallet = Wallet::where('user_id', $landlord->user_id)->first();

            // If the landlord does not have a wallet, create one
            if (!$landlordWallet) {
                $landlordWallet = Wallet::create([
                    'user_id' => $landlord->user_id,
                    'amount' => 0, // Initialize with zero balance
                ]);
            }

            // Handle payment via wallet
            if ($paymentChannel == 'wallet') {
                // Find the wallet for the authenticated user (tenant)
                $wallet = Wallet::where('user_id', $userId)->first();

                // Check if wallet exists
                if (!$wallet) {
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Wallet not found for this user.',
                    ], 404);
                }

                // Check if the wallet has enough balance
                if ($wallet->amount < $amount) {
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Insufficient balance in the wallet.',
                    ], 400);
                }

                // Remove the full amount from the tenant's wallet
                $wallet->removeBalance($amount);

                // Add the amount minus service fee to the landlord's wallet
                $landlordWallet->addBalance($landlordAmount);

                // Record the rent payment with service fee information
                $this->recordRentPayment($tenant->id, $landlord->id, $amount, 'wallet', null, $serviceFee, $landlordAmount);

                // Return success response
                return response()->json([
                    'status' => 'success',
                    'message' => 'Rent paid successfully!',
                    'details' => [
                        'total_paid' => $amount,
                        'service_fee' => $serviceFee,
                        'landlord_receives' => $landlordAmount
                    ]
                ], 200);
            }

            // If the payment method selected is MPesa
            if ($paymentChannel == 'mpesa') {
                // Logic for MPESA payment
                $response = $this->initiateStkPush($request->phone, $amount, $payment_user_id);

                if ($response['status'] == 'success') {
                    $billRef = $response['data']['account_reference'];

                    // Dispatch the job to check transaction status in the background
                    // Pass the calculated amounts to the job
                    CheckTransactionStatus::dispatch(
                        $billRef,
                        $landlord->user_id,
                        $amount,
                        $tenant->id,
                        $landlord->id,
                        $serviceFee,
                        $landlordAmount
                    );

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Rent payment initiated successfully!',
                        'details' => [
                            'total_paid' => $amount,
                            'service_fee' => $serviceFee,
                            'landlord_receives' => $landlordAmount
                        ]
                    ], 200);
                } else {
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Failed to initiate STK push.',
                    ], 500);
                }
            }

            // If no valid payment channel is selected
            return response()->json([
                'status' => 'failed',
                'message' => 'No payment method selected.',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to process rent payment: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Record rent payment in the database.
     */
    public function recordRentPayment($tenantId, $landlordId, $amount, $paymentMethod, $transactionId = null)
    {
        Log::info("Recording rent payment. Tenant ID: {$tenantId}, Landlord ID: {$landlordId}, Amount: {$amount}, Payment Method: {$paymentMethod}, Transaction ID: {$transactionId}");

        $unpaidMonths = $this->getUnpaidMonths($tenantId);

        // If there are no unpaid months, carry forward the payment to the next month
        if (empty($unpaidMonths)) {
            // Get the last recorded payment month
            $lastPaymentMonth = RentPayments::where('tenant_id', $tenantId)
                ->orderBy('month', 'desc')
                ->value('month');

            if ($lastPaymentMonth) {
                // If there is a last payment month, calculate the next month
                $nextMonth = date('Y-m', strtotime($lastPaymentMonth . ' +1 month'));
            } else {
                // If there are no payments at all, use the current month
                $nextMonth = now()->format('Y-m');
            }

            $unpaidMonths[] = $nextMonth;
        }

        // Record the payment for the oldest unpaid month
        $month = reset($unpaidMonths); // Get the oldest unpaid month
        RentPayments::create([
            'tenant_id' => $tenantId,
            'landlord_id' => $landlordId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
            'payment_date' => now(),
            'month' => $month,
            'status' => 'paid'
        ]);

        Log::info("Rent payment recorded successfully for month: {$month}");
    }

    private function getUnpaidMonths($tenantId)
    {
        $unpaidMonths = [];
        $currentMonth = now()->format('Y-m');
        $tenant = Tenant::find($tenantId);
        $joinDate = $tenant->created_at;

        // Loop through each month since the tenant joined
        for ($date = $joinDate; $date->format('Y-m') <= $currentMonth; $date->addMonth()) {
            $month = $date->format('Y-m');
            $rentPaid = RentPayments::where('tenant_id', $tenantId)
                ->where('month', $month)
                ->exists();

            if (!$rentPaid) {
                $unpaidMonths[] = $month;
            }
        }

        return $unpaidMonths;
    }
}
