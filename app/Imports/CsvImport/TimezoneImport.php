<?php

namespace App\Imports\CsvImport;

use App\Models\TimeZone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class TimezoneImport implements ToCollection
{
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        Schema::disableForeignKeyConstraints();
        TimeZone::truncate();
        Schema::enableForeignKeyConstraints();
        foreach ($rows as $key => $row) {
            if ($key == 0 && $row[1] != '時間帯') {
                return redirect()->back()->withErrors(['message' => '時間帯区分CSVファイルではないか形式が正しくありません。']);
            }
            if ($key > 0) {
                TimeZone::create([
                    'detail_times' => $row[1]
                ]);
            }
        }
    }
}
