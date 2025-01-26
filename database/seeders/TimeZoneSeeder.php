<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('time_zones')->insert([
            ['detail_times' => '1日'],
            ['detail_times' => '午前'],
            ['detail_times' => '午後'],
            ['detail_times' => '8:00~10:00'],
            ['detail_times' => '10:00~12:00'],
            ['detail_times' => '13:00~15:00'],
            ['detail_times' => '15:00~17:00'],
        ]);
    }
}
