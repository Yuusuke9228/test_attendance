<?php

namespace App\Imports;

use App\Models\DakouChild;
use App\Models\DakouData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class AttendDataImport implements ToCollection
{
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        Schema::disableForeignKeyConstraints();
        DakouData::truncate();
        DakouChild::truncate();

        foreach ($rows as $key => $row) {
            if ($key == 0 && $row[3] != '打刻区分') {
                return redirect()->back()->withErrors(['message' => '出勤管理CSVファイルではないか形式が正しくありません。']);
            }
            if ($key > 0) {
                if($row[0]) {
                    DakouData::create([
                        "id"                     => (int)$row[0],
                        "target_date"            => $row[1],
                        "dp_user"                => (int)$row[2],
                        "dp_status"              => (int)$row[3],
                        "dp_syukkin_time"        => $row[4],
                        "dp_taikin_time"         => $row[5],
                        "dp_break_start_time"    => $row[6],
                        "dp_break_end_time"      => $row[7],
                        "dp_gaishutu_start_time" => $row[8],
                        "dp_gaishutu_end_time"   => $row[9],
                        "dp_ride_flg"            => $row[10],
                        "dp_other_flg"           => (int)$row[11],
                        "dp_memo"                => $row[12],
                        "dp_dakou_address"       => $row[13],
                        "dp_made_by"             => (int)$row[14],
                    ]);
                }
                if ($row[17]) {
                    DakouChild::create([
                        'dp_dakoku_id'            => (int)$row[17],
                        'dp_occupation_id'        => (int)$row[19],
                        'dp_timezone_id'          => (int)$row[20],
                        'dp_support_flg'          => (int)$row[21],
                        'dp_support_company_id'   => $row[22] != null ? (int)$row[22] : null,
                        'dp_supported_company_id' => $row[23] != null ? (int)$row[23] : null,
                        'dp_nums_of_people'       => $row[24] != null ? (int)$row[24] : null,
                        'dp_work_contens_id'      => (int)$row[25],
                        'dp_work_location_id'     => (int)$row[26],
                        'dp_workers_master'       => (int)$row[27],
                        'dp_workers'              => $row[28],
                    ]);
                }
            }
        }
        Schema::enableForeignKeyConstraints();
    }
}
