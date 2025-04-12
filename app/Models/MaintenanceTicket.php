<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'property_id',
        'issue',
        'description',
        'status', // "open", "closed"
        'priority', // "low", "high"
        'ticket_status', // "pending", "in_progress", "complete"
        'image',
        'technician_notes', // Add technician_notes to the fillable array
        'closed_by_service_provider_id'
    ];

    // Relationship: A maintenance ticket belongs to a service provider
    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id');
    }

    // Relationship: A maintenance ticket can have many ratings (if users rate the ticket)
    public function ratings()
    {
        return $this->hasMany(ProviderRating::class, 'ticket_id');
    }
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
    // Add this relationship
    public function property()
    {
        return $this->belongsTo(Properties::class, 'property_id');
    }
}
