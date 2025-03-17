<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Tenant extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone_number',
        'password',
        'property_id',
        'house_no',
        'image'
    ];

    // Each tenant belongs to one property
    public function property()
    {
        return $this->belongsTo(Properties::class, 'property_id');
    }

    public function moveOutRequests()
    {
        return $this->hasMany(MoveOutRequest::class);
    }
    // Relationship: A user can leave many ratings for service providers
    public function providerRatings()
    {
        return $this->hasMany(ProviderRating::class, 'user_id');
    }

    public function notifications()
    {
        return $this->morphMany(Notifications::class, 'user');
    }

    // Relationship: A tenant can have many rent payments
    public function rentPayments()
    {
        return $this->hasMany(RentPayments::class);
    }
}
