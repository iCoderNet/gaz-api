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
        Schema::table('azot_price_types', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Eski yozuvlarga UUID yozib chiqamiz
        DB::table('azot_price_types')->orderBy('id')->chunkById(100, function ($azot_price_types) {
            foreach ($azot_price_types as $a_p_t) {
                DB::table('azot_price_types')
                    ->where('id', $a_p_t->id)
                    ->update(['uuid' => \Illuminate\Support\Str::uuid()]);
            }
        });

        // Keyin unique constraint qo‘shamiz
        Schema::table('azot_price_types', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->change();
        });
    }

    public function down()
    {
        Schema::table('azot_price_types', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }

};
