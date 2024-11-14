<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoveOutRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'move_out_date',
        'move_out_reason',
        'status', // Optional: For tracking the status of the request (e.g., pending, approved, rejected)
    ];

    // A move-out request belongs to a tenant
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
