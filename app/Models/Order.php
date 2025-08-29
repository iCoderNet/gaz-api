<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'promocode_id', 'promo_price', 'price_type', 'cargo_price',  'address', 'phone', 'comment',  'all_price', 'total_price', 'status'
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

    public function toArray()
    {
        $array = parent::toArray();
        $array['status_code'] = $this->status;
        $array['status'] = $this->status_text;
        
        return $array;
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
}
