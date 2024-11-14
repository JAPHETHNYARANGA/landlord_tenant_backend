<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Model
{
    use HasApiTokens,HasFactory;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'address',
    ];

     /**
     * Get all the notifications for the admin.
     */
    public function notifications()
    {
        return $this->morphMany(Notifications::class, 'user');
    }
}
