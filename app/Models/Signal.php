<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signal extends Model
{
    protected $fillable = [
        'asset_id',
        'entry_price',
        'target_price',
        'stop_loss',
        'expected_gain',
        'expired_at',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
