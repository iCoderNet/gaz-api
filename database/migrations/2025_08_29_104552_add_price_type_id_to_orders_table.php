<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_azots', function (Blueprint $table) {
            $table->unsignedBigInteger('price_type_id')->nullable(); 
        });
    }

    public function down(): void
    {
        Schema::table('order_azots', function (Blueprint $table) {
            $table->dropColumn('price_type_id');
        });
    }
};

