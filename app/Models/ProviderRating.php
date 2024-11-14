<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderRating extends Model
{
    use HasFactory;
    protected $fillable = [
        'ticket_id',
        'user_id',
        'service_provider_id',
        'rating',
        'comment',
    ];

    // Define relationships
    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'user_id');
    }

    public function ticket()
    {
        return $this->belongsTo(MaintenanceTicket::class, 'ticket_id');
    }
}
