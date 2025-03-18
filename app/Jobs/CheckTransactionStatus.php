<?php

namespace App\Jobs;

use App\Http\Controllers\WalletController;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\TransactionStatusNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckTransactionStatus implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected $billRef;
    protected $userId;
    protected $amount;
    protected $tenantId; // Add tenantId
    protected $landlordId; // Add landlordId

    /**
     * Create a new job instance.
     */
    public function __construct($billRef, $userId, $amount, $tenantId, $landlordId)
    {
        $this->billRef = $billRef;
        $this->userId = $userId;
        $this->amount = $amount;
        $this->tenantId = $tenantId; // Initialize tenantId
        $this->landlordId = $landlordId; // Initialize landlordId
    }

    /**
     * Execute the job.
     */
    public $tries = 5;

    public function handle()
    {
        // Log the job parameters for debugging
        Log::info("Job parameters - BillRef: {$this->billRef}, User ID: {$this->userId}, Amount: {$this->amount}, Tenant ID: {$this->tenantId}, Landlord ID: {$this->landlordId}");
    
        // Get the wallet
        $wallet = Wallet::where('user_id', $this->userId)->first();
    
        if (!$wallet) {
            Log::error("Wallet not found for user ID: {$this->userId}");
            throw new \Exception("Wallet not found");
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
    
            // Record the rent payment
            $controller->recordRentPayment($this->tenantId, $this->landlordId, $this->amount, 'mpesa', $this->billRef);
            Log::info("Recorded M-Pesa payment. Tenant ID: {$this->tenantId}, Landlord ID: {$this->landlordId}, Amount: {$this->amount}, Payment Method: 'mpesa', Transaction ID: {$this->billRef}");
        } else {
            // Handle any other state, but don't throw an exception for other states
            Log::warning("Unexpected response for BillRef {$this->billRef}: {$response}");
        }
    }
}