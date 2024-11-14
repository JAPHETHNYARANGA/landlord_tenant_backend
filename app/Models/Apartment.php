<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'location',
        'bedrooms',
        'bathrooms',
        'price',
        'description',
    ];

    public function images()
    {
        return $this->hasMany(ApartmentImage::class);
    }
}
