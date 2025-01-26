<?php

namespace Database\Seeders;

use App\Models\Occupation;
use App\Models\WorkContent;
use Exception;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OccupationAndWorkContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $arr = [
            [
                "key" => 0,
                "label" => '現場（大工）',
                "children" => [
                    ["key" => 0, "label" => "デッキ張り"],
                    ["key" => 1, "label" => "地中梁組立"],
                    ["key" => 2, "label" => "上部カベ・柱"],
                    ["key" => 3, "label" => "立上り・止め枠・溝"],
                    ["key" => 4, "label" => "コンクリ相番"],
                    ["key" => 5, "label" => "ひらいだし"],
                    ["key" => 6, "label" => "掃除"],
                    ["key" => 7, "label" => "その他"],
                ]
            ],
            [
                "key" => 1,
                "label" => '現場（解体）',
                "children" => [
                    ["key" => 0, "label" => "腰壁"],
                    ["key" => 1, "label" => "地中梁"],
                    ["key" => 2, "label" => "掃除"],
                    ["key" => 3, "label" => "上部"],
                    ["key" => 4, "label" => "釘じまい"],
                    ["key" => 5, "label" => "基礎ビット"],
                    ["key" => 6, "label" => "その他"],
                ]
            ],
            [
                "key" => 2,
                "label" => '加工',
                "children" => [
                    ["key" => 0, "label" => "加工"],
                ]
            ],
            [
                "key" => 3,
                "label" => 'その他',
                "children" => [
                    ["key" => 0, "label" => "搬入"],
                    ["key" => 1, "label" => "搬出"],
                    ["key" => 2, "label" => "その他"],
                ]
            ],
        ];
        try {
            foreach ($arr as $items) {
                $occp_id = DB::table('occupations')->insertGetId(
                    [
                        'occupation_name' => $items['label'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
                foreach ($items['children'] as $content) {
                    DB::table('work_contents')->insert(
                        [
                            'work_content_occp_id' => $occp_id,
                            'work_content_name' => $content['label'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                }
            }
        } catch (Exception $e) {
            dd($e);
        }
    }
}
