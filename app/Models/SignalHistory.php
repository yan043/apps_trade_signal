<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignalHistory extends Model
{
    protected $table = 'signal_histories';

    protected $fillable = [
        'symbol',
        'signal_type',
        'signal',
        'signal_price',
        'entry_price',
        'close_price',
        'stop_loss',
        'take_profit_1',
        'take_profit_2',
        'take_profit_3',
        'highest_high',
        'lowest_low',
        'status',
        'outcome',
        'realized_r',
        'days_held',
        'percent_change',
        'sent_at',
        'closed_at',
        'extra',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'closed_at' => 'datetime',
        'extra' => 'array',
    ];
}
