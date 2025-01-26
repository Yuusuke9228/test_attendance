<?php

namespace App\Imports;

use App\Models\SupportedCompany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class SupportedCompanyImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        Schema::disableForeignKeyConstraints();
        SupportedCompany::truncate();
        Schema::enableForeignKeyConstraints();
        foreach ($rows as $key => $row) {
            if ($key == 0 && $row[1] != '応援先会社名') {
                return redirect()->back()->withErrors(['message' => '応援先会社名情報CSVファイルではないか形式が正しくありません。']);
            }
            if ($key > 0) {
                SupportedCompany::create([
                    'supported_company_name'    => $row[1],
                    'supported_company_person'  => $row[2],
                    'supported_company_email'   => $row[3],
                    'supported_company_tel'     => $row[4],
                    'supported_company_zipcode' => $row[5],
                    'supported_company_address' => $row[6],
                ]);
            }
        }
    }
}
