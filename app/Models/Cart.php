<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'product_id',
        'price_type_id',
        'quantity'
    ];
}
