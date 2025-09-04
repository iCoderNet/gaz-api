<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // tg_id ustunidan unique indexni olib tashlash
            $table->dropUnique('users_tg_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // rollback qilinganda yana unique tiklash
            $table->unique('tg_id', 'users_tg_id_unique');
        });
    }
};
