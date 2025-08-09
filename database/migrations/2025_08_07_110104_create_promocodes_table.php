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
        Schema::create('promocodes', function (Blueprint $table) {
            $table->id();
            $table->string('promocode')->unique();
            $table->decimal('amount', 12, 0);
            $table->enum('status', ['active', 'archive', 'deleted'])->default('active');
            $table->enum('type', ['countable', 'fixed-term']);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('countable')->nullable();
            $table->integer('used_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocodes');
    }
};
