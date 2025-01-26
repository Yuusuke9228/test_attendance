<?php

namespace App\Imports;

use App\Models\User;
use App\Models\UserData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class UserImport implements ToCollection
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        foreach ($rows as $key => $row) {
            if ($key == 0 && $row[1] != 'code') {
                return redirect()->back()->withErrors(['message' => 'ユーザー管理CSVファイルではないか形式が正しくありません。']);
            }
            if ($key > 0) {
                $code = rtrim(str_replace('="', '', $row[1]), '"');
                $user = User::updateOrCreate(
                    [
                        'code' => $code,
                    ],
                    [
                        'name'              => $row[2],
                        'email'             => $row[3],
                        'email_verified_at' => $row[4],
                        'password'          => $row[5],
                        'role'              => (int)$row[6],
                        'status'            => (int)$row[7],
                        'available'         => (int)$row[8],
                    ]
                );
                if ($row[9]) {
                    UserData::updateOrCreate(
                        [
                            'user_id'              => $user->id,
                        ],
                        [
                            'work_pattern_code_id' => (int)$row[10],
                        ]
                    );
                }
            }
        }
    }
}
