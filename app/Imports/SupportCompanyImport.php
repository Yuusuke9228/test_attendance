<?php

namespace App\Imports;

use App\Models\SupportCompany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class SupportCompanyImport implements ToCollection
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        Schema::disableForeignKeyConstraints();
        SupportCompany::truncate();
        Schema::enableForeignKeyConstraints();
        foreach ($rows as $key => $row) {
            if ($key == 0 && $row[1] != '応援会社名') {
                return redirect()->back()->withErrors(['message' => '応援に来てもらう会社 CSVファイルではないか形式が正しくありません。']);
            }
            if ($key > 0) {
                SupportCompany::create([
                    'support_company_name'    => $row[1],
                    'support_company_person'  => $row[2],
                    'support_company_email'   => $row[3],
                    'support_company_tel'     => $row[4],
                    'support_company_zipcode' => $row[5],
                    'support_company_address' => $row[6],
                ]);
            }
        }
    }
}
