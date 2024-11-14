<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'property_id',
        'amount',
        'payment_date',
        'payment_method',
    ];
}
