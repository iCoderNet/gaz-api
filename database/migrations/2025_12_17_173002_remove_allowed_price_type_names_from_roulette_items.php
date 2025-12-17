<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roulette_items', function (Blueprint $table) {
            $table->dropColumn('allowed_price_type_names');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roulette_items', function (Blueprint $table) {
            $table->json('allowed_price_type_names')->nullable()->after('is_active');
        });
    }
};
