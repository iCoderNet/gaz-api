<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouletteSpin extends Model
{
    // Faqat created_at ishlatamiz, updated_at kerak emas
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'order_id',
        'roulette_item_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relationship: Aylantirgan foydalanuvchi
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Qaysi buyurtmada aylantirgan
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relationship: Nima yutilgan
     */
    public function rouletteItem()
    {
        return $this->belongsTo(RouletteItem::class);
    }

    /**
     * Scope: Ma'lum user uchun
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Bugungi aylantirishlar
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
