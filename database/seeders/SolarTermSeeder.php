<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use LogicException;

/** @deprecated master_solar_terms は正式計算に使用しない。 */
class SolarTermSeeder extends Seeder
{
    public function run(): void
    {
        throw new LogicException('SolarTermSeeder は正式採用不可です。SolarTermEventSeeder を使用してください。');
    }
}
