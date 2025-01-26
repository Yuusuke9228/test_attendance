<?php

namespace App\Imports;

use App\Models\WorkContent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class WorkContentImport implements ToCollection
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        Schema::disableForeignKeyConstraints();
        WorkContent::truncate();
        Schema::enableForeignKeyConstraints();
        foreach ($rows as $key => $row) {
            if ($key == 0 && $row[2] != '作業名') {
                return redirect()->back()->withErrors(['message' => '作業内容CSVファイルではないか形式が正しくありません。']);
            }
            if ($key > 0) {
                WorkContent::create([
                    'work_content_occp_id' => (int)$row[1],
                    'work_content_name'    => $row[2],
                ]);
            }
        }
    }
}
