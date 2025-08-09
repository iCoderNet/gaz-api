<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Azot extends Model
{
    protected $fillable = [
        'title', 'type', 'image', 'description', 'country', 'status'
    ];

    protected $appends = ['image_url'];

    public function priceTypes()
    {
        return $this->hasMany(AzotPriceType::class);
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        // Agar "storage:link" ishlatilgan bo‘lsa:
        return asset('storage/' . $this->image);
    }
}
