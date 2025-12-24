<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('azot_price_types', function (Blueprint $table) {
            $table->boolean('roulette_allowed')->default(true)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('azot_price_types', function (Blueprint $table) {
            $table->dropColumn('roulette_allowed');
        });
    }
};
