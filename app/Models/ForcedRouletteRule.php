<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForcedRouletteRule extends Model
{
    protected $fillable = [
        'azot_id',
        'price_type_name',
        'roulette_item_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: Qaysi azot uchun
     */
    public function azot()
    {
        return $this->belongsTo(Azot::class);
    }

    /**
     * Relationship: Qaysi sovg'a beriladi
     */
    public function rouletteItem()
    {
        return $this->belongsTo(RouletteItem::class);
    }

    /**
     * Scope: Faqat faol qoidalar
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
