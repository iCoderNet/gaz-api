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
        Schema::table('promocodes', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Eski yozuvlarga UUID yozib chiqamiz
        DB::table('promocodes')->orderBy('id')->chunkById(100, function ($promocodes) {
            foreach ($promocodes as $promocode) {
                DB::table('promocodes')
                    ->where('id', $promocode->id)
                    ->update(['uuid' => \Illuminate\Support\Str::uuid()]);
            }
        });

        // Keyin unique constraint qo‘shamiz
        Schema::table('promocodes', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->change();
        });
    }

    public function down()
    {
        Schema::table('promocodes', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }

};
