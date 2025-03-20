<?php

namespace App\Jobs;

use App\Http\Controllers\WalletController;
use App\Models\Wallet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckWalletRechargeStatus implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected $billRef;
    protected $userId;
    protected $amount;


    /**
     * Create a new job instance.
     */
    public function __construct($billRef, $userId, $amount)
    {
        $this->billRef = $billRef;
        $this->userId = $userId;
        $this->amount = $amount;
 
    }

    /**
     * Execute the job.
     */
    public $tries = 5;

    public function handle()
    {
        // Log the job parameters for debugging
        Log::info("wallet Recharge parameters - BillRef: {$this->billRef}, User ID: {$this->userId}, Amount: {$this->amount}");
    
        // Get the wallet
        $wallet = Wallet::where('user_id', $this->userId)->first();
    
        if (!$wallet) {
            Log::error(" wallet Recharge Wallet not found for user ID: {$this->userId}");
            throw new \Exception("Wallet not found");
        }
    
        // Call the checkTransactionStatus method from the WalletController
        $controller = new WalletController();
        $response = $controller->checkTransactionStatus($this->billRef);
    
        // Log the response for debugging purposes
        Log::info("wallet Recharge Transaction check for BillRef {$this->billRef}: {$response}");
    
        if ($response == 'failed') {
            // If the transaction failed, release the job with a delay to retry
            Log::warning("wallet Recharge Transaction failed for BillRef {$this->billRef}. Retrying in 1 minute...");
            $this->release(60);  // This will retry the job after 1 minute
            return; // Return to stop further execution
        }
    
        if ($response == 'success') {
            // Add funds to the wallet if the transaction was successful
            $wallet->addBalance($this->amount);
            Log::info(" wallet RechargeTransaction successful. Added {$this->amount} to wallet for user ID: {$this->userId}");
    
        } else {
            // Handle any other state, but don't throw an exception for other states
            Log::warning(" wallet Recharge Unexpected response for BillRef {$this->billRef}: {$response}");
        }
    }
}
