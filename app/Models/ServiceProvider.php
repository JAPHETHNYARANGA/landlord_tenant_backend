<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class ServiceProvider extends Model
{
    use HasApiTokens,HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone_number',
        'password',
        'designation'
    ];

     // Relationship: A service provider can have many maintenance tickets
     public function maintenanceTickets()
     {
         return $this->hasMany(MaintenanceTicket::class, 'service_provider_id');
     }
 
     // Relationship: A service provider can have many ratings
     public function ratings()
     {
         return $this->hasMany(ProviderRating::class, 'service_provider_id');
     }
 
     // Relationship: A service provider can have one user (in case the user is the service provider itself)
     public function user()
     {
         return $this->belongsTo(User::class, 'user_id'); // Assumes each service provider has an associated user (optional)
     }
 
     // Get the average rating for the service provider
     public function averageRating()
     {
         return $this->ratings()->avg('rating'); // Calculate the average rating based on provider ratings
     }
 
     // Get the total number of ratings for this service provider
     public function totalRatings()
     {
         return $this->ratings()->count();
     }
 
     // Optionally, you can define a method to fetch the service provider's name or designation
     public function getFullName()
     {
         return "{$this->name} - {$this->designation}";
     }
}
