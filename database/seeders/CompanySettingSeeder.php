<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('company_settings')->insert([
            'company_name' => '株式会社　ナナキ',
            'company_kana' => 'カブシキガイシャ　ナナキ',
            'company_zip_code' => '929-0122',
            'company_tel01' => '0761-55-5069',
            'company_tel02' => '0761-55-5089',
            'company_addr01' => '石川県能美市大浜',
            'company_addr02' => '町ケ63-6',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
