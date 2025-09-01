<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('additional_services', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Eski yozuvlarga UUID yozib chiqamiz
        DB::table('additional_services')->orderBy('id')->chunkById(100, function ($additional_services) {
            foreach ($additional_services as $service) {
                DB::table('additional_services')
                    ->where('id', $service->id)
                    ->update(['uuid' => \Illuminate\Support\Str::uuid()]);
            }
        });

        // Keyin unique constraint qo‘shamiz
        Schema::table('additional_services', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->change();
        });
    }

    public function down()
    {
        Schema::table('additional_services', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }

};
