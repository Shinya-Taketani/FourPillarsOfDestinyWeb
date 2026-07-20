<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use LogicException;

/** @deprecated 近似式データは正式計算に使用しない。 */
class SolarTermSeeder2 extends Seeder
{
    public function run(): void
    {
        throw new LogicException('SolarTermSeeder2 の近似式は正式採用不可です。SolarTermEventSeeder を使用してください。');
    }
}
