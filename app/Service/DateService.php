<?php

namespace App\Service;

use App\Models\Holiday;
use Carbon\Carbon;

class DateService
{
    public function min_date($close_status, $close_date)
    {
        if ($close_status === null || $close_date === null) return null;

        if ($close_status == 1) {
            $year  = date("Y", strtotime($close_date));
            $month = date("m", strtotime($close_date));
            $date  = date("d");
            $current_date = Carbon::create($year, $month, $date)->addMonth();
        } else {
            $current_date = Carbon::now();
        }

        $i = 0;
        $holidays = Holiday::where('paid_holiday', 0)
            ->where('holiday_flag', 1)
            ->whereDate('holiday_date', '>', Carbon::now()->subDays(10)->format("Y-m-d"))
            ->whereDate('holiday_date', '<=', Carbon::now()->format("Y-m-d"))
            ->pluck('holiday_date')?->toArray();

        do {
            $min_date = $current_date->subDay();
            if (!in_array(date('w', strtotime($min_date)), [0, 6]) && !in_array(Carbon::parse($min_date)->format("Y-m-d"), $holidays ?? [])) {
                if(date("d", strtotime($min_date)) == 1 && $close_status == 1) break;
                $i++;
            }
        } while ($i < 5);
        return Carbon::parse($min_date)->format("Y-m-d");
    }
}
