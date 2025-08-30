<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAzot extends Model
{
    protected $fillable = [
        'order_id',
        'azot_id',
        'price_type_id',
        'count',
        'price',
        'total_price',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function azot()
    {
        return $this->belongsTo(Azot::class);
    }
}
