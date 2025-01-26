<?php

namespace App\Imports;

use App\Models\BreakTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class BreakTimeImport implements ToCollection
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        Schema::disableForeignKeyConstraints();
        BreakTime::truncate();
        Schema::enableForeignKeyConstraints();
        foreach ($rows as $key => $row) {
            if ($key == 0 && $row[1] != '勤務形態コード') {
                return redirect()->back()->withErrors(['message' => '休憩時間・勤務形態管理CSVファイルではないか形式が正しくありません。']);
            }
            if ($key > 0) {
                BreakTime::create([
                    'break_work_pattern_cd' => $row[1],
                    'break_start_time'      => $row[2],
                    'break_end_time'        => $row[3],
                    'break_organization'    => (int)$row[4],
                    'break_name'            => $row[5],
                    'break_start_time1'     => $row[6],
                    'break_end_time1'       => $row[7],
                    'break_start_time2'     => $row[8],
                    'break_end_time2'       => $row[9],
                    'break_start_time3'     => $row[10],
                    'break_end_time3'       => $row[11],
                ]);
            }
        }
    }
    
}
