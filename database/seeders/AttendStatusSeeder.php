<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $names = [
            '1.5H 早退',
            '2H 早退',
            '2.5H 早退',
            '3H 早退',
            '1H 残業',
            '1.5H 残業',
            '2H 残業',
            '2.5H 残業',
            '3H 残業',
            '0.5H 遅刻',
            '1H 遅刻',
            '1.5H 遅刻',
            '有給休暇',
            '冠婚葬祭',
            '健康診断',
            '資格取得講習・試験',
            '勉強会',
            '新入生関係',
            'その他',
        ];
        DB::transaction(function () use ($names) {
            foreach($names as $value) {
                DB::table('attend_statuses')->insert([
                    'attend_name' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
