<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('message_batches', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->json('stats')->nullable();       // array sifatida cast qilinadi
            $table->string('status')->default('pending');
            $table->json('user_ids')->nullable();    // array sifatida cast qilinadi
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_batches');
    }
};
