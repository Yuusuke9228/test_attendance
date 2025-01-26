<?php

namespace App\Imports;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ScheduleCsvImport implements ToCollection
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        Schema::disableForeignKeyConstraints();
        Schedule::truncate();
        Schema::enableForeignKeyConstraints();
        try {
            foreach ($rows as $key => $row) {
                var_export($row);
                if ($key == 0 && $row[1] != '従業員') {
                    return redirect()->back()->withErrors(['message' => '作業予定管理CSVファイルではないか形式が正しくありません。']);
                }
                if ($key > 0) {
                    Schedule::create([
                        'user_id'             => $row[1],
                        'location_id'         => (int)$row[2],
                        'schedule_date'       => Carbon::parse($row[3])->format('Y-m-d'),
                        'schedule_start_time' => $row[4],
                        'schedule_end_time'   => $row[5],
                        'occupation_id'       => (int)$row[6],
                    ]);
                }
            }
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }
}
