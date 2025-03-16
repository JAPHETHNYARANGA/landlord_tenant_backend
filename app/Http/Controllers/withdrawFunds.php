<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class withdrawFunds extends Controller
{
    public function withdrawFunds(Request $request){
        try{


        }catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to process rent payment: ' . $e->getMessage(),
            ], 500);
        }
    }
}
