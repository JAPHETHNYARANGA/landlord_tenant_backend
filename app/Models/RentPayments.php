<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentPayments extends Model
{
    use HasFactory;

      // Define the fillable properties
      protected $fillable = [
        'tenant_id',
        'landlord_id',
        'amount',
        'payment_method',
        'transaction_id',
        'payment_date',
        'month',
    ];

    // Disable timestamps if you don't need them for this model (e.g., for custom timestamps)
    // public $timestamps = false;

    /**
     * Relationship with Tenant model
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship with Landlord model
     */
    public function landlord()
    {
        return $this->belongsTo(Landlord::class);
    }

}
