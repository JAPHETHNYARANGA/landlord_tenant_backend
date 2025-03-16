<?php

namespace App\Models;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword as PasswordReset;


class Landlord extends Model implements CanResetPassword
{
    use HasApiTokens, HasFactory, Notifiable, PasswordReset;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone_number',
        'address',
        'password',
        'image'
    ];

    public function properties()
    {
        return $this->hasMany(Properties::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notifications::class, 'user');
    }

  
}
