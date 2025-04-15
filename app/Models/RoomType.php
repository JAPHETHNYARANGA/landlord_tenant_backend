<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'type',
        'count',
        'price'
    ];

    public function property()
    {
        return $this->belongsTo(Properties::class, 'property_id'); // Specify the foreign key explicitly
    }

    // In app/Models/RoomType.php
    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'property_id', 'property_id')
                ->where('room_type', $this->type);
    }
}
