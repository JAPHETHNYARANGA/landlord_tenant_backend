<?php

namespace App\Jobs;

use App\Http\Controllers\WalletController;
use App\Models\Wallet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;  // Import the Log facade

class CheckTransactionStatus implements ShouldQueue
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
        // Get the wallet
        $wallet = Wallet::where('user_id', $this->userId)->first();

        if (!$wallet) {
            Log::error("Wallet not found for user ID: {$this->userId}");
            throw new \Exception("Wallet not found");  // Throw an exception to trigger retry
        }

        // Call the checkTransactionStatus method from the WalletController
        $controller = new WalletController();
        $response = $controller->checkTransactionStatus($this->billRef);

        // Log the response for debugging purposes
        Log::info("Transaction check for BillRef {$this->billRef}: {$response}");

        if ($response == 'failed') {
            // If the transaction failed, release the job with a delay to retry
            Log::warning("Transaction failed for BillRef {$this->billRef}. Retrying in 1 minute...");
            $this->release(60);  // This will retry the job after 1 minute
            return; // Return to stop further execution
        }

        if ($response == 'success') {
            // Add funds to the wallet if the transaction was successful
            $wallet->addBalance($this->amount);
            Log::info("Transaction successful. Added {$this->amount} to wallet for user ID: {$this->userId}");
        } else {
            // Handle any other state, but don't throw an exception for other states
            Log::warning("Unexpected response for BillRef {$this->billRef}: {$response}");
        }
    }

    
}
