<?php

namespace Database\Seeders;

use App\Models\AttendType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        AttendType::truncate();
        Schema::enableForeignKeyConstraints();
        DB::table('attend_types')->insert(
            [
                [
                    'attend_type_name' => '出勤',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'attend_type_name' => '退勤',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                // [
                //     'attend_type_name' => '未出勤',
                //     'created_at' => now(),
                //     'updated_at' => now()
                // ],
            ]
        );
    }
}
