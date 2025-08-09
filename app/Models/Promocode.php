<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promocode extends Model
{
    protected $fillable = ['promocode', 'amount', 'status', 'type', 'start_date', 'end_date', 'countable', 'used_count'];
}
