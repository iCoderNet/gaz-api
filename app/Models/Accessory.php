<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accessory extends Model
{
    protected $fillable = ['title', 'price', 'image', 'description', 'status'];

    protected $appends = ['image_url'];


    public function getImageUrlAttribute()
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : null;
    }
}
