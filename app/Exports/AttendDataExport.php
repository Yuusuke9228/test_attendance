<?php

namespace App\Exports;

use App\Models\AttendStatus;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendDataExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $data;
    public function __construct($data)
    {
        $this->data = $data;
    }
    public function collection()
    {
        return $this->data;
    }
    public function map($row): array
    {
        $date = Carbon::parse($row->target_date)->format('Y/m/d');
        Carbon::setLocale('ja');
        $weekday = Carbon::parse($row->target_date)->translatedFormat('l');
        $weekday = mb_substr($weekday, 0, 1);
        $name = $row->user?->name;
        $organization = $row->user?->user_data?->break_times;
        $syukkin = $organization?->break_start_time ? Carbon::parse($organization->break_start_time)->format('H:i') : "";
        $real_syukkin = $row->dp_syukkin_time ? Carbon::parse($row->dp_syukkin_time)->format('H:i') : "";
        $taikin = $organization?->break_end_time ? Carbon::parse($organization->break_end_time)->format('H:i') : "";
        $real_taikin = $row->dp_taikin_time ? Carbon::parse($row->dp_taikin_time)->format('H:i') : "";
        $code = $row->user?->code;

        // 1.5H 早退	2H 早退	2.5H 早退	3H 早退	1H 残業	1.5H 残業	2H 残業	2.5H 残業	3H 残業	0.5H 遅刻	1H 遅刻	1.5H 遅刻	有給休暇	冠婚葬祭	健康診断	資格取得講習・試験	勉強会	新入生関係	その他
        $other_flg_id = $row->attend_status?->id;
        // var_export($other_flg_id);die;

        $row_data = [$date . " " . $weekday, $name, $syukkin, $real_syukkin, $taikin, $real_taikin, $code];
        $other_flgs = AttendStatus::count();
        for($i = 0; $i<$other_flgs; $i++) {
            $row_data[] = "";
        }
        if($other_flg_id) {
            $row_data[6 + $other_flg_id] = '/';
        }
        return $row_data;
    }

    public function headings(): array
    {
        $header_i = ['(年月日)', '姓 名', '出勤時刻', '出勤実時刻', '退勤時刻', '退勤実時刻', 'スタッフコード'];
        $others = AttendStatus::pluck('attend_name');
        $header = array_merge($header_i, $others->toArray());
        return [
            $header,
        ];
    }
}
