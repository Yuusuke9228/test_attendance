<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BreakTimesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $break_times = [
            [
                'break_work_pattern_cd' => 'fe1',
                'break_start_time' => '08:00:00',
                'break_end_time' => '17:00:00',
                'break_organization' => 1,
                'break_name' => '現場',
                'break_start_time1' => '10:00:00',
                'break_end_time1' => '10:30:00',
                'break_start_time2' => '12:00:00',
                'break_end_time2' => '13:00:00',
                'break_start_time3' => '15:00:00',
                'break_end_time3' => '15:30:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'break_work_pattern_cd' => 'pa1',
                'break_start_time' => '09:00:00',
                'break_end_time' => '15:00:00',
                'break_organization' => 1,
                'break_name' => 'パート',
                'break_start_time1' => '10:00:00',
                'break_end_time1' => '10:30:00',
                'break_start_time2' => '12:00:00',
                'break_end_time2' => '13:00:00',
                'break_start_time3' => '15:00:00',
                'break_end_time3' => '15:30:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'break_work_pattern_cd' => 'fe2',
                'break_start_time' => '08:00:00',
                'break_end_time' => '17:00:00',
                'break_organization' => 1,
                'break_name' => '事務',
                'break_start_time1' => '10:00:00',
                'break_end_time1' => '10:30:00',
                'break_start_time2' => '12:00:00',
                'break_end_time2' => '13:00:00',
                'break_start_time3' => '15:00:00',
                'break_end_time3' => '15:30:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'break_work_pattern_cd' => 'naka1',
                'break_start_time' => '08:00:00',
                'break_end_time' => '17:00:00',
                'break_organization' => 2,
                'break_name' => '事務',
                'break_start_time1' => '10:00:00',
                'break_end_time1' => '10:30:00',
                'break_start_time2' => '12:00:00',
                'break_end_time2' => '13:00:00',
                'break_start_time3' => '15:00:00',
                'break_end_time3' => '15:30:00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'break_work_pattern_cd' => 'naka2',
                'break_start_time' => '08:00:00',
                'break_end_time' => '17:00:00',
                'break_organization' => 2,
                'break_name' => '現場',
                'break_start_time1' => '10:00:00',
                'break_end_time1' => '10:30:00',
                'break_start_time2' => '12:00:00',
                'break_end_time2' => '13:00:00',
                'break_start_time3' => '15:00:00',
                'break_end_time3' => '15:30:00',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];
        DB::table('break_times')->insert($break_times);
    }
}
