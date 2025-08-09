<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'promocode_id', 'promo_price', 'cargo_price', 'address', 'phone', 'comment',  'all_price', 'total_price', 'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function promocode()
    {
        return $this->belongsTo(Promocode::class);
    }

    public function azots()
    {
        return $this->hasMany(OrderAzot::class);
    }

    public function accessories()
    {
        return $this->hasMany(OrderAccessory::class);
    }

    public function services()
    {
        return $this->hasMany(OrderService::class);
    }
}
