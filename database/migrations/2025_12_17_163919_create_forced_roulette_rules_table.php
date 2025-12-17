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
        Schema::create('forced_roulette_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('azot_id')->constrained('azots')->onDelete('cascade');
            $table->string('price_type_name')->comment('To\'lov turi nomi, masalan: Nalich, Výkup');
            $table->foreignId('roulette_item_id')->constrained('roulette_items')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Bir azot + to'lov turi uchun faqat bitta qoida
            $table->unique(['azot_id', 'price_type_name']);

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forced_roulette_rules');
    }
};
