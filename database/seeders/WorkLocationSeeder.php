<?php

namespace Database\Seeders;

use Exception;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::transaction(function () {
            try {
                DB::table('work_locations')->insert([
                    [
                        'location_flag' => 1,
                        'location_name' => '太陽生命',
                        'location_address' => '金沢市西念町地内',
                        'created_at' => now(),
                        'updated_at' => now()
                    ],
                    [
                        'location_flag' => 1,
                        'location_name' => '加賀東芝',
                        'location_address' => '能美市岩内町地内',
                        'created_at' => now(),
                        'updated_at' => now()
                    ],
                    [
                        'location_flag' => 1,
                        'location_name' => '彩の庭',
                        'location_address' => null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ],
                    [
                        'location_flag' => 1,
                        'location_name' => '小林製作所',
                        'location_address' => null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ],
                    [
                        'location_flag' => 1,
                        'location_name' => 'サクラバックス',
                        'location_address' => null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ],
                    [
                        'location_flag' => 1,
                        'location_name' => '鳴和土場',
                        'location_address' => null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ],

                    [
                        'location_flag' => 1,
                        'location_name' => '美大ゴミ庫',
                        'location_address' => null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ],
                    [
                        'location_flag' => 1,
                        'location_name' => '高岡基礎',
                        'location_address' => null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ],
                ]);
            } catch (Exception $e) {
                dd($e);
                DB::rollback();
            }
        });
    }
}
