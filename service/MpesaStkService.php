<?php

namespace App\Services;

use App\Models\MpesaStkPayments;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class MpesaStkService
{
    /**
     * Initiates the STK push request to M-PESA.
     *
     * @param array $stkData Array containing the necessary data for STK push.
     * @return array Response from M-PESA or error message.
     */
    public function lipaNaMpesaStk(array $stkData): array
    {
        try {
            // Extract the data from the incoming array
            $consumerKey = $stkData['consumer_key'] ?? env('MPESA_CONSUMER_KEY');
            $consumerSecret = $stkData['consumer_secret'] ?? env('MPESA_CONSUMER_SECRET');
            $shortCode = $stkData['shortcode'];
            $passkey = $stkData['passkey'];
            $amount = $stkData['amount'];
            $partyA = $stkData['msisdn']; // Customer's phone number
            $accountReference = $stkData['account_reference'];
            $stkCallbackUrl = $stkData['stk_callback'];
            $partyB = $stkData['organization_code']; // Till or Paybill number
            $transactionType = $stkData['transaction_type'];

            // Get the M-PESA STK URL from the environment file
            $stkInitiateUrl = env('SAF_STK_URL');  // Ensure this URL is set in your .env

            // Get the password (which is the encoded shortcode + passkey + timestamp)
            $password = $this->getPassword($shortCode, $passkey);

            // Fetch access token from cache or generate a new one
            $accessToken = Cache::get('safaricom_stk_access_token');
            if (!$accessToken) {
                $response = $this->getAccessToken($consumerKey, $consumerSecret);
                if (isset($response['error'])) {
                    Log::error('Failed to fetch access token: ' . $response['error']);
                    return $response;
                }
                $accessToken = $response['access_token'];
                $expiry = $response['expires_in'];

                // Store token in cache for future use
                Cache::put('safaricom_stk_access_token', $accessToken, now()->addSeconds($expiry));
            }

            // Prepare the request data for M-PESA STK push
            $timestamp = Carbon::now()->format('YmdHis');
            $postData = [
                'BusinessShortCode' => $shortCode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => $transactionType,
                'Amount' => $amount,
                'PartyA' => $partyA,
                'PartyB' => $partyB,
                'PhoneNumber' => $partyA,
                'CallBackURL' => $stkCallbackUrl,
                'AccountReference' => $accountReference,
                'TransactionDesc' => $partyA . " is paying " . $amount . " to " . $shortCode,
            ];

            // Set up the headers for the API request
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ];

            // Send the request to M-PESA's STK push endpoint
            $client = new Client();
            $response = $client->post($stkInitiateUrl, [
                'json' => $postData,
                'headers' => $headers,
            ]);

            // Parse the response from M-PESA
            $responseBody = json_decode($response->getBody()->getContents(), true);

            // Check if the response was successful
            if ($responseBody['ResponseCode'] == '0') {
                return $responseBody;
            }

            // Log error if STK Push failed
            Log::error('STK Push failed: ' . json_encode($responseBody));

            return ['error' => $responseBody['ResponseDescription']];
        } catch (\Exception $e) {
            Log::error('An error occurred while initiating STK Push: ' . $e->getMessage());
            return ['error' => 'An unexpected error occurred'];
        }
    }

    /**
     * Saves the STK Payment details to the database.
     *
     * @param array $data STK callback data containing payment details.
     * @param string $shortcode The shortcode to identify the business.
     * @return array Success or error message.
     */
    public function saveStkPayment(array $data, string $shortcode): array
    {
        try {
            // You can save the payment details to the database here
            // MpesaStkPayments::create([
            //     'merchant_request_id' => $data['MerchantRequestID'],
            //     'checkout_request_id' => $data['CheckoutRequestID'],
            //     'shortcode' => $shortcode,
            // ]);

            return ['success' => 'Payment details saved successfully for ' . $data['CheckoutRequestID']];
        } catch (\Exception $e) {
            Log::error('Failed to save payment details: ' . $e->getMessage());
            return ['error' => 'Failed to save payment details'];
        }
    }

    /**
     * Generates the password for the STK push request.
     *
     * @param string $shortcode The shortcode for the business.
     * @param string $passkey The passkey for the business.
     * @return string The encoded password.
     */
    private function getPassword(string $shortcode, string $passkey): string
    {
        $timestamp = Carbon::now()->format('YmdHis');
        return base64_encode($shortcode . $passkey . $timestamp);
    }

    /**
     * Fetches the access token from M-PESA.
     *
     * @param string $consumerKey The consumer key for the API.
     * @param string $consumerSecret The consumer secret for the API.
     * @return array The response containing the access token or an error message.
     */
    private function getAccessToken(string $consumerKey, string $consumerSecret): array
    {
        try {
            $authUrl = env('SAF_AUTH_URL'); // Ensure this URL is set in your .env file
            $client = new Client();

            // Generate the Basic Auth token
            $authToken = base64_encode("{$consumerKey}:{$consumerSecret}");
            $headers = ['Authorization' => 'Basic ' . $authToken];

            // Send the request to fetch the access token
            $response = $client->get($authUrl, ['headers' => $headers]);

            // Parse the response and return the access token
            $responseBody = json_decode($response->getBody()->getContents(), true);

            return [
                'access_token' => $responseBody['access_token'],
                'expires_in' => $responseBody['expires_in'],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch access token: ' . $e->getMessage());
            return ['error' => 'Failed to fetch access token'];
        }
    }
}
