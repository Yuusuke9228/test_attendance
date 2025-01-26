<?php

namespace App\Imports;

use App\Models\AttendStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class AttendStatusImport implements ToCollection
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        Schema::disableForeignKeyConstraints();
        AttendStatus::truncate();
        Schema::enableForeignKeyConstraints();
        foreach ($rows as $key => $row) {
            if ($key == 0 && $row[1] != '選択肢表示名') {
                return redirect()->back()->withErrors(['message' => '残業・早退・遅刻CSVファイルではないか形式が正しくありません。']);
            }
            if ($key > 0) {
                AttendStatus::create([
                    'attend_name' => $row[1]
                ]);
            }
        }
    }
}
