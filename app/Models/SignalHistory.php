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
        'close_price',
        'percent_change',
        'sent_at',
        'extra',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'extra' => 'array',
    ];
}
