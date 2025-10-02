<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('signals', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->decimal('entry_price', 15, 2);
            $table->decimal('target_price', 15, 2);
            $table->decimal('stop_loss', 15, 2);
            $table->decimal('expected_gain', 6, 2);
            $table->string('reason');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('signals');
    }
};
