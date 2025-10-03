<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signal extends Model
{
    protected $fillable = [
        'asset_id',
        'entry_price',
        'target_price',
        'target_price_2',
        'target_price_3',
        'stop_loss',
        'expected_gain',
        'expected_gain_2',
        'expected_gain_3',
        'expired_at',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
