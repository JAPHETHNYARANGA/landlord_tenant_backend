<?php

namespace App\Http\Controllers;

use App\Models\Payments;
use App\Models\Tenant;
use App\Services\MpesaStkService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
 
    public function getTenantTransactions(Request $request)
    {
        try {

            $user = Auth::user()->id;

            // Access validated billref_no
            $billref_no = $user;

            // URL of the external API (first server)
            $apiUrl = 'https://payment.cityrealtykenya.com/api/mpesa/customerTransactions';

            // Send a POST request to the first server with billref_no
            $response = Http::post($apiUrl, [
                'billref_no' => $billref_no
            ]);

            // Check if the response is successful
            if ($response->successful()) {
                // Return the data received from the first server
                return response()->json([
                    'status' => true,
                    'data' => $response->json()['data']  // Assuming the response contains a 'data' key
                ], 200);
            } else {
                // If the external API request fails
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to fetch transaction data from external server'
                ], 500);
            }
        } catch (\Throwable $th) {
            // Handle any exceptions that may occur during the process
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


    public function getAllTransactions(Request $request)
    {
        try {
            // URL of the external API to get all transactions
            $apiUrl = 'https://payment.cityrealtykenya.com/api/mpesa/getAllTransactions';

            // Send a GET request to the external API to fetch all transactions
            $response = Http::get($apiUrl);  // GET request to fetch all transactions

            // Check if the response is successful
            if ($response->successful()) {
                // Get the transaction data
                $transactions = $response->json()['data'];

                // Map over transactions and fetch tenant info based on billref_no
                foreach ($transactions as &$transaction) {
                    // Fetch tenant info by billref_no
                    $tenant = Tenant::where('id', $transaction['billref_no'])->first();

                    // If tenant is found, append tenant information to transaction
                    if ($tenant) {
                        $transaction['tenant_name'] = $tenant->name;
                        $transaction['tenant_email'] = $tenant->email;
                        $transaction['tenant_phone'] = $tenant->phone_number;
                        $transaction['tenant_address'] = $tenant->address;
                        $transaction['tenant_property'] = $tenant->property->name ?? 'N/A'; // Assuming relationship with property
                    } else {
                        // If tenant not found, add placeholders
                        $transaction['tenant_name'] = 'N/A';
                        $transaction['tenant_email'] = 'N/A';
                        $transaction['tenant_phone'] = 'N/A';
                        $transaction['tenant_address'] = 'N/A';
                        $transaction['tenant_property'] = 'N/A';
                    }
                }

                // Return the data received from the external server with tenant info added
                return response()->json([
                    'status' => true,
                    'data' => $transactions
                ], 200);
            } else {
                // If the external API request fails
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to fetch all transactions from external server'
                ], 500);
            }
        } catch (\Throwable $th) {
            // Handle any exceptions that may occur during the process
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


    public function getLandlordTransactions(){
        try {
            // Get the authenticated landlord's ID
            $landlordId = Auth::user()->id;
    
            // URL of the external API to get all transactions
            $apiUrl = 'https://payment.cityrealtykenya.com/api/mpesa/getAllTransactions';
    
            // Send a GET request to the external API to fetch all transactions
            $response = Http::get($apiUrl);  // GET request to fetch all transactions
    
            // Check if the response is successful
            if ($response->successful()) {
                // Get the transaction data
                $transactions = $response->json()['data'];
    
                // Prepare an array to store the landlord-specific transactions
                $landlordTransactions = [];
    
                // Iterate through each transaction and filter based on landlord's properties
                foreach ($transactions as &$transaction) {
                    // Fetch tenant info by billref_no
                    $tenant = Tenant::where('id', $transaction['billref_no'])->first();
    
                    // If tenant is found
                    if ($tenant) {
                        // Get the property associated with the tenant
                        $property = $tenant->property;
    
                        // Check if the tenant's property belongs to the current landlord
                        if ($property && $property->landlord_id == $landlordId) {
                            // If the property belongs to the landlord, append tenant and property info to the transaction
                            $transaction['tenant_name'] = $tenant->name;
                            $transaction['tenant_email'] = $tenant->email;
                            $transaction['tenant_phone'] = $tenant->phone_number;
                            $transaction['tenant_address'] = $tenant->address;
                            $transaction['tenant_property'] = $property->name ?? 'N/A';  // Property name
    
                            // Add this transaction to the landlord's list of transactions
                            $landlordTransactions[] = $transaction;
                        }
                    } else {
                        // If tenant not found, add placeholders
                        $transaction['tenant_name'] = 'N/A';
                        $transaction['tenant_email'] = 'N/A';
                        $transaction['tenant_phone'] = 'N/A';
                        $transaction['tenant_address'] = 'N/A';
                        $transaction['tenant_property'] = 'N/A';
                    }
                }
    
                // Return the landlord-specific transactions
                return response()->json([
                    'status' => true,
                    'data' => $landlordTransactions
                ], 200);
            } else {
                // If the external API request fails
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to fetch transactions from the external server.'
                ], 500);
            }
        } catch (\Throwable $th) {
            // Handle any exceptions that may occur during the process
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    

   
}
