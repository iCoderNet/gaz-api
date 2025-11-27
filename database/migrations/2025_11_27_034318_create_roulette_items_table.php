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
        Schema::create('roulette_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accessory_id')->nullable()->constrained('accessories')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('probability', 5, 2)->default(0)->comment('Tushish foizi (0-100)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index for better performance
            $table->index('is_active');
            $table->index('probability');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roulette_items');
    }
};
