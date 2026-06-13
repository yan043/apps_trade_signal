<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signal_histories', function (Blueprint $table)
        {
            if (!Schema::hasColumn('signal_histories', 'entry_price'))
            {
                $table->double('entry_price')->nullable()->after('signal_price');
            }
            if (!Schema::hasColumn('signal_histories', 'stop_loss'))
            {
                $table->double('stop_loss')->nullable()->after('close_price');
            }
            if (!Schema::hasColumn('signal_histories', 'take_profit_1'))
            {
                $table->double('take_profit_1')->nullable()->after('stop_loss');
            }
            if (!Schema::hasColumn('signal_histories', 'take_profit_2'))
            {
                $table->double('take_profit_2')->nullable()->after('take_profit_1');
            }
            if (!Schema::hasColumn('signal_histories', 'take_profit_3'))
            {
                $table->double('take_profit_3')->nullable()->after('take_profit_2');
            }
            if (!Schema::hasColumn('signal_histories', 'highest_high'))
            {
                $table->double('highest_high')->nullable()->after('take_profit_3');
            }
            if (!Schema::hasColumn('signal_histories', 'lowest_low'))
            {
                $table->double('lowest_low')->nullable()->after('highest_high');
            }
            if (!Schema::hasColumn('signal_histories', 'status'))
            {
                $table->string('status')->default('open')->after('lowest_low');
            }
            if (!Schema::hasColumn('signal_histories', 'outcome'))
            {
                $table->string('outcome')->nullable()->after('status');
            }
            if (!Schema::hasColumn('signal_histories', 'realized_r'))
            {
                $table->double('realized_r')->nullable()->after('outcome');
            }
            if (!Schema::hasColumn('signal_histories', 'days_held'))
            {
                $table->integer('days_held')->default(0)->after('realized_r');
            }
            if (!Schema::hasColumn('signal_histories', 'closed_at'))
            {
                $table->timestamp('closed_at')->nullable()->after('days_held');
            }
        });
    }

    public function down(): void
    {
        Schema::table('signal_histories', function (Blueprint $table)
        {
            $columns = [
                'entry_price', 'stop_loss', 'take_profit_1', 'take_profit_2', 'take_profit_3',
                'highest_high', 'lowest_low', 'status', 'outcome', 'realized_r', 'days_held', 'closed_at',
            ];

            foreach ($columns as $column)
            {
                if (Schema::hasColumn('signal_histories', $column))
                {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
