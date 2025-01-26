<?php

namespace App\Imports;

use App\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class OrganizationImport implements ToCollection
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        Schema::disableForeignKeyConstraints();
        Organization::truncate();
        Schema::enableForeignKeyConstraints();
        foreach ($rows as $key => $row) {
            if ($key == 0 && $row[1] != '親組織') {
                return redirect()->back()->withErrors(['message' => '組織CSVファイルではないか形式が正しくありません。']);
            }
            if ($key > 0) {
                Organization::create([
                    'organization_parent_name' => $row[1],
                    'organization_code'        => (int)$row[2],
                    'organization_name'        => $row[3],
                    'organization_zipcode'     => $row[4],
                    'organization_address'     => $row[5],
                    'organization_master_name' => $row[6],
                ]);
            }
        }
    }
}
