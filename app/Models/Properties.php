<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Properties extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'rooms',
        'type',
        'status',
        'landlord_id',
        'guard_phone'
    ];

    public function landlord()
    {
        return $this->belongsTo(Landlord::class);
    }

    public function roomTypes()
    {
        return $this->hasMany(RoomType::class, 'property_id'); // Specify the foreign key explicitly
    }


    // One property can have many tenants
    // In Properties.php
    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'property_id');
    }
}
