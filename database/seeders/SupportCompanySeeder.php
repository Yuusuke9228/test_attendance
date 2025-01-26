<?php

namespace Database\Seeders;

use App\Models\SupportCompany;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupportCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $arr = [
            '佐々木組',
            '南組',
            '岡山組',
            '株式会社カネショウ工務店',
            '株式会社ナカセ',
        ];
        foreach ($arr as $val) {
            SupportCompany::create(
                [
                    'support_company_name' => $val
                ]
            );
        }
    }
}
