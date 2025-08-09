<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderService extends Model
{
    protected $fillable = [
        'order_id',
        'additional_service_id',
        'count',
        'price',
        'total_price',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
