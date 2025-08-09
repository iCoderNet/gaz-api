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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('promocode_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('promo_price', 12, 0)->default(0);
            $table->text('cargo_price')->nullable();
            $table->decimal('all_price', 12, 0)->default(0);
            $table->decimal('total_price', 12, 0)->default(0);
            $table->text('address')->nullable();
            $table->text('phone')->nullable();
            $table->text('comment')->nullable();
            $table->enum('status', ['new', 'pending', 'accepted', 'rejected', 'completed', 'deleted'])->default('new');
            $table->timestamps();
        });

        Schema::create('order_azots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('azot_id')->constrained()->onDelete('cascade');
            $table->integer('count')->default(1);
            $table->decimal('price', 12, 0)->default(0);
            $table->decimal('total_price', 12, 0)->default(0);
            $table->timestamps();
        });

        Schema::create('order_accessories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('accessory_id')->constrained()->onDelete('cascade');
            $table->integer('count')->default(1);
            $table->decimal('price', 12, 0)->default(0);
            $table->decimal('total_price', 12, 0)->default(0);
            $table->timestamps();
        });

        Schema::create('order_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('additional_service_id')->constrained()->onDelete('cascade');
            $table->integer('count')->default(1);
            $table->decimal('price', 12, 0)->default(0);
            $table->decimal('total_price', 12, 0)->default(0);
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('order_services');
        Schema::dropIfExists('order_accessories');
        Schema::dropIfExists('order_azots');
        Schema::dropIfExists('orders');

        Schema::enableForeignKeyConstraints();
    }
};
