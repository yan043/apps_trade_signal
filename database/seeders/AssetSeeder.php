<?php

namespace Database\Seeders;

use App\Models\Asset;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $assets = [
            ['symbol' => 'BTCUSDT', 'market' => 'crypto'],
            ['symbol' => 'ETHUSDT', 'market' => 'crypto'],
            ['symbol' => 'ADAUSDT', 'market' => 'crypto'],
            ['symbol' => 'SOLUSDT', 'market' => 'crypto'],
            ['symbol' => 'DOTUSDT', 'market' => 'crypto'],
            ['symbol' => 'BBCA.JK', 'market' => 'stock'],
            ['symbol' => 'TLKM.JK', 'market' => 'stock'],
            ['symbol' => 'ASII.JK', 'market' => 'stock'],
            ['symbol' => 'UNVR.JK', 'market' => 'stock'],
            ['symbol' => 'BMRI.JK', 'market' => 'stock'],
        ];

        foreach ($assets as $asset) {
            Asset::create($asset);
        }
    }
}
