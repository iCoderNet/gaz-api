<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAccessory extends Model
{
    protected $fillable = [
        'order_id',
        'accessory_id',
        'count',
        'price',
        'total_price',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
