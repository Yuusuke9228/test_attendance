<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(AdminSeeder::class);
        $this->call(PrefectureListSeeder::class);
        $this->call(OrganizationSeeder::class);
        $this->call(AttendStatusSeeder::class);
        $this->call(AttendTypeSeeder::class);
        $this->call(OccupationAndWorkContentSeeder::class);
        $this->call(WorkLocationSeeder::class);
        $this->call(HolidaysSeeder::class);
        $this->call(SupportCompanySeeder::class);
        $this->call(SupportedCompanySeeder::class);
        $this->call(BreakTimesSeeder::class);
        $this->call(CompanySettingSeeder::class);
        $this->call(TimeZoneSeeder::class);
    }
}
