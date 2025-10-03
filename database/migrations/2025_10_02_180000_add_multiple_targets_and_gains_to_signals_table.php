<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('signals', function (Blueprint $table)
        {
            $table->decimal('target_price_2', 15, 2)->nullable()->after('target_price');
            $table->decimal('target_price_3', 15, 2)->nullable()->after('target_price_2');
            $table->decimal('expected_gain_2', 6, 2)->nullable()->after('expected_gain');
            $table->decimal('expected_gain_3', 6, 2)->nullable()->after('expected_gain_2');
        });
    }

    public function down()
    {
        Schema::table('signals', function (Blueprint $table)
        {
            $table->dropColumn(['target_price_2', 'target_price_3', 'expected_gain_2', 'expected_gain_3']);
        });
    }
};
