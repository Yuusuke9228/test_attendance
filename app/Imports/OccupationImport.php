<?php

namespace App\Imports;

use App\Models\Occupation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class OccupationImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        Schema::disableForeignKeyConstraints();
        Occupation::truncate();
        Schema::enableForeignKeyConstraints();
        foreach ($rows as $key => $row) {
            if ($key == 0 && $row[1] != '職種名') {
                return redirect()->back()->withErrors(['message' => '職種管理CSVファイルではないか形式が正しくありません。']);
            }
            if ($key > 0) {
                Occupation::create([
                    'occupation_name' => $row[1],
                ]);
            }
        }
    }
}
