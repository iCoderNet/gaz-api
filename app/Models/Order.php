<?php

namespace App\Models;

use App\Helpers\OrderHelper;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'promocode_id', 'phone', 'address', 'comment', 
        'cargo_price', 'promo_price', 'all_price', 'total_price', 
        'status', 'payment_type', 'is_hidden_for_user'
    ];

    protected $hidden = [
        'is_hidden_for_user'
    ];

    protected $casts = [
        'is_hidden_for_user' => 'boolean',
        'cargo_price' => 'integer',
        'promo_price' => 'integer',
        'all_price' => 'integer',
        'total_price' => 'integer',
        'order_number' => 'integer',
    ];

    protected $appends = ['status_text'];

    public function getStatusTextAttribute()
    {
        $statuses = [
            'new' => 'не оформлен',
            'pending' => 'оформлен',
            'accepted' => 'принято',
            'rejected' => 'отклонено',
            'completed' => 'завершено',
            'deleted' => 'deleted',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->order_number = OrderHelper::generateUniqueOrderNumber();
        });
    }
}