<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('organizations')->insert([
            [
                'organization_code' => '0001',
                'organization_name' => '株式会社ナナキ',
                'organization_parent_name' => null,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'organization_code' => '0002',
                'organization_name' => '株式会社ナカセ',
                'organization_parent_name' => null,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'organization_code' => '0003',
                'organization_name' => '森組',
                'organization_parent_name' => '株式会社ナナキ',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'organization_code' => '0004',
                'organization_name' => '佐々木組',
                'organization_parent_name' => '森組',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
