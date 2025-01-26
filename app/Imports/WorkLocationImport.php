<?php

namespace App\Imports;

use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class WorkLocationImport implements ToCollection
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        Schema::disableForeignKeyConstraints();
        WorkLocation::truncate();
        Schema::enableForeignKeyConstraints();
        foreach ($rows as $key => $row) {
            if ($key == 0 && $row[2] != '現場名') {
                return redirect()->back()->withErrors(['message' => '現場管理CSVファイルではないか形式が正しくありません。']);
            }
            if ($key > 0) {
                WorkLocation::create([
                    'location_flag'    => (int)$row[1],
                    'location_name'    => $row[2],
                    'location_address' => $row[3],
                ]);
            }
        }
    }
}
