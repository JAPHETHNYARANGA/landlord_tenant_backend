<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

     // Define the fillable attributes
     protected $fillable = ['user_id', 'amount'];

     /**
      * Add funds to the wallet.
      */
     public function addBalance($amount)
     {
         $this->amount += $amount;
         $this->save();
     }

     /**
      * Remove funds from the wallet.
      */
     public function removeBalance($amount)
     {
         if ($this->amount >= $amount) {
             $this->amount -= $amount;
             $this->save();
         } else {
             throw new \Exception("Insufficient balance");
         }
     }
 
     /**
      * Get current balance.
      */
     public function getBalance()
     {
         return $this->amount;
     }
}
