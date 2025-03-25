<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class KplcController extends Controller
{
    public function buyTokens(Request $request)
    {
        // Step 1: Authenticate and get the access token

        $client = new Client();
        
        try {
            $response = $client->request('POST', 'https://tandaio-api-uats.tanda.co.ke/accounts/v1/oauth/token', [
                'form_params' => [
                    'grant_type' => 'client_credentials'
                ],
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $accessToken = $data['access_token'] ?? null;

            if (!$accessToken) {
                return response()->json(['error' => 'Unable to retrieve access token'], 400);
            }

            // Step 2: Use the access token to make the request to buy tokens

            $response = $client->request('POST', 'https://tandaio-api-uats.tanda.co.ke/io/v2/organizations/bc49f3dc-c09d-4fe8-a243-82bfd555a666/requests', [
                'body' => json_encode([
                    'commandId' => 'VoucherFlexi',
                    'serviceProviderId' => 'KPLC',
                    'reference' => 'ref00001', // You can dynamically replace the reference
                ]),
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken, // Pass the access token
                ],
            ]);

            $tokenResponse = json_decode($response->getBody(), true);

            // Step 3: Return the response to the user
            return response()->json($tokenResponse);
        } catch (\Exception $e) {
            // Catch any errors that occur during the process
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
