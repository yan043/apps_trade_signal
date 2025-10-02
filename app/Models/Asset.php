<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = ['symbol', 'market'];

    public function signals()
    {
        return $this->hasMany(Signal::class);
    }
}
