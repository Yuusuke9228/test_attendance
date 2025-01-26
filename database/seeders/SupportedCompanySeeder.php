<?php

namespace Database\Seeders;

use App\Models\SupportedCompany;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupportedCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $arr = [
            '株式会社ナカセ',
            '株式会社一藤',
            '株式会社宮崎工業',
            '長谷川建設株式会社',
        ];
        foreach ($arr as $val) {
            SupportedCompany::create(
                [
                    'supported_company_name' => $val
                ]
            );
        }
    }
}
