<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HolidaysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $holidays = ['2023-06-02', '2023-04-08', '2023-04-09', '2023-04-15', '2023-04-23', '2023-04-29','2023-04-30','2023-05-03','2023-05-04','2023-05-05','2023-05-06','2023-05-07','2023-05-13','2023-05-14','2023-05-20','2023-05-21','2023-05-28'];
        $holidays = collect($holidays)->map(function ($item) {
            return [
                'holiday_flag' => 1,
                'holiday_date' => $item,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();
        DB::table('holidays')->insert($holidays);
    }
}
