<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AzotPriceType extends Model
{
    protected $fillable = ['azot_id', 'name', 'price'];

    public function azot()
    {
        return $this->belongsTo(Azot::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
}
