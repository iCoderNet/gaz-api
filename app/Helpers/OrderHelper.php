<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class OrderHelper
{
    public static function generateUniqueOrderNumber($length = 4)
    {
        while ($length <= 10) {
            for ($i = 0; $i < 3; $i++) {
                $number = str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
                $exists = DB::table('orders')->where('order_number', $number)->exists();

                if (!$exists) {
                    return $number;
                }
            }
            $length++;
        }

        throw new \Exception('Unique order number could not be generated.');
    }
}
