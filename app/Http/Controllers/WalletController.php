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
    
            // Call the STK Push API to initiate the payment
            $response = $this->initiateStkPush($request->phone, $request->amount, $userId);
    
            // Check if the STK push was initiated successfully
            if ($response['status'] == 'success') {
                // Now, wait for the confirmation callback from M-PESA
                // Assuming you have a method to handle the callback (it could be a webhook from M-PESA)
                // Once the callback confirms success, update the wallet balance
    
                // For now, let's assume we directly check the response after STK push (simulate success here):
                // Let's say $response contains a 'transaction_id' which will be used in callback to confirm payment
    
               

                $billRef = $response['data']['account_reference'];
                
                sleep(120);
                
                $transactionStatus = $this->checkTransactionStatus($billRef); // A method to check the status
    
                if ($transactionStatus == 'success') {
                    // Add balance to the wallet if the transaction was successful
                    $wallet->addBalance($request->amount);
    
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Funds added successfully!',
                        'balance' => $wallet->amount,
                    ]);
                } else {
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Transaction failed. Funds were not added.',
                    ], 500);
                }
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
    private function checkTransactionStatus($transactionId)
    {
        // This method should interact with M-PESA to get the status of the transaction
        // You can use the MpesaDataFetchController's fetchCustomerTransaction or a similar method
        
        try {
            // Call your endpoint or method that fetches transaction status
            $response = Http::post('https://payment.cityrealtykenya.com/api/mpesa/confirmTransactions', [
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


    public function payRent(Request $request){
        try{
            // Validate the incoming request
            $request->validate([
                'amount' => 'required|numeric|min:0.01', // Ensure the amount is positive
                'phone'=>'nullable',
                'paymentChannel'=>'required'
            ]);

            $paymentChannel = $request->paymentChannel;

            if($request->paymentChannel == 'mpesa'){
                $wallet_Channel = $request->paymentChannel;
            }else if($request->paymentChannel == 'wallet'){
                $mpesa_Channel = $request->paymentChannel;
            }else{
                return response()->json([
                    'message' =>'no payment method selected'
                ]);
            }


            if($wallet_Channel){
                //logic to remove wallet funds

            }else if($mpesa_Channel){
                //logic for mpesa

                // Call the STK Push API to initiate the payment
                $response = $this->initiateStkPush($request->phone, $request->amount, $userId);

                // Check if the STK push was initiated successfully
                if ($response['status'] == 'success') {               

                    $billRef = $response['data']['account_reference'];
                    
                    sleep(120);
                    
                    $transactionStatus = $this->checkTransactionStatus($billRef); // A method to check the status
        
                    if ($transactionStatus == 'success') {
                      
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Rent paid successfully!',
                        ],200);
                    } else {
                        return response()->json([
                            'status' => 'failed',
                            'message' => 'Transaction failed. Rent was not paid.',
                        ], 500);
                    }
                } else {
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Failed to initiate STK push.',
                    ], 500);
                }
            }

        }catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to fetch balance: ' . $e->getMessage(),
            ], 500);
        }
    }
}
