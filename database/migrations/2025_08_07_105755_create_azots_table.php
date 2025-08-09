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
        Schema::create('azots', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->string('country');
            $table->enum('status', ['active', 'archive', 'deleted'])->default('active');
            $table->timestamps();
        });

        Schema::create('azot_price_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('azot_id')->constrained()->onDelete('cascade');
            $table->string('name'); // obmen, arenda
            $table->decimal('price', 12, 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('azot_price_types');
        Schema::dropIfExists('azots');
    }
};
