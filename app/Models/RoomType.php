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
    ];

    public function property()
    {
        return $this->belongsTo(Properties::class, 'property_id'); // Specify the foreign key explicitly
    }

}
