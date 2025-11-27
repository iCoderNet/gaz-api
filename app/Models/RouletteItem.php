<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouletteItem extends Model
{
    protected $fillable = [
        'accessory_id',
        'title',
        'description',
        'image',
        'probability',
        'is_active',
    ];

    protected $casts = [
        'probability' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = ['image_url'];

    /**
     * Relationship: Aksessuar bilan bog'lanish
     */
    public function accessory()
    {
        return $this->belongsTo(Accessory::class);
    }

    /**
     * Relationship: Bu elementning barcha aylantirishlari
     */
    public function spins()
    {
        return $this->hasMany(RouletteSpin::class);
    }

    /**
     * Accessor: Rasmning to'liq URL'ini qaytarish
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        return asset('storage/' . $this->image);
    }

    /**
     * Scope: Faqat faol elementlar
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
