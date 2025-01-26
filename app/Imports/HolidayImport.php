<?php

namespace App\Imports;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class HolidayImport implements ToCollection
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        Schema::disableForeignKeyConstraints();
        Holiday::truncate();
        Schema::enableForeignKeyConstraints();
        foreach ($rows as $key => $row) {
            if ($key == 0 && $row[1] != '休日') {
                return redirect()->back()->withErrors(['message' => '休日管理 CSVファイルではないか形式が正しくありません。']);
            }
            if ($key > 0) {
                Holiday::create([
                    'holiday_flag' => (int)$row[1],
                    'holiday_date' => Carbon::parse($row[2])->format('Y-m-d'),
                ]);
            }
        }
    }
}
