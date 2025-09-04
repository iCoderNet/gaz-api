<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Avvalgi unique indexni olib tashlaymiz
            $table->dropUnique('users_tg_id_unique');

            // Endi tg_id + status kombinatsiyasi bo‘yicha unique
            $table->unique(['tg_id', 'status'], 'users_tg_id_status_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Yangi indexni olib tashlash
            $table->dropUnique('users_tg_id_status_unique');

            // Eski unique indexni qayta tiklash
            $table->unique('tg_id', 'users_tg_id_unique');
        });
    }
};
