<?php

namespace App\Helper;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Http;
use Ramsey\Uuid\Provider\Time\FixedTimeProvider;
use Maatwebsite\Excel\Excel;

class Helper
{
    public static function secondsToTime($seconds)
    {
        if ($seconds >= 3600 * 24) {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return sprintf('%02d', $hours) . ":" . sprintf('%02d', $minutes);
        } else {
            return CarbonInterval::seconds($seconds)->cascade()->format('%H:%I');
        }
    }
    public static function getWeekday($date)
    {
        $weeks = ["日", "月", "火", "水", "木", "金", "土", "日"];
        if ($date) {
            $n_w = date("w", strtotime($date));
            return $weeks[$n_w];
        }
    }

    public static function getJpDate($date, $format)
    {
        $formated_api_url = "http://ap.hutime.org/cal/?method=conv&ical=101.1&itype=%s&ival=%s&ocal=1001.1";
        $api_url = sprintf($formated_api_url, $format, $date);
        try {
            $japan_date = Http::get($api_url)->body(); // 和暦年
            $japan_date = str_replace("\r\n", "", $japan_date);
            if (strpos($japan_date, '年') !== false) {
                return $japan_date;
            } else {
                if (strpos($date, '/') !== false) {
                    $y = explode('/', $date)[0];
                } else {
                    $y = $date;
                }
                return $y . "年";
            }
        } catch (\Exception $e) {
            return $date;
        }
    }
    public static function every(array $array, callable $callback)
    {
        return array_reduce(
            $array,
            function ($carry, $item) use ($callback) {
                return $carry && call_user_func($callback, $item);
            },
            true
        );
    }

    public static function array_some(array $array, callable $callback): bool
    {
        foreach ($array as $element) {
            if ($callback($element)) {
                return true;
            }
        }
        return false;
    }

    public static function get_calendar_object($start_date, $start_week = 0)
    {
        $weekdays = [
            ['label' => "⽇", 'value' => 0],
            ['label' => "⽉", 'value' => 1],
            ['label' => "⽕", 'value' => 2],
            ['label' => "⽔", 'value' => 3],
            ['label' => "⽊", 'value' => 4],
            ['label' => "⾦", 'value' => 5],
            ['label' => "⼟", 'value' => 6],
        ];
        $start_date = new \DateTime($start_date);
        $days_list  = [];

        for ($i = 0; $i < 7; $i++) {
            $day_cell = $weekdays[$i];
            array_push($days_list, $day_cell);
        }

        // reassign the day_list order by weekday setting
        if ($start_week !== 7) {
            $sw = $start_week;
        } else {
            $sw = $start_date->format('w');
        }

        for ($weekHeIndex = 0; $weekHeIndex < $sw; $weekHeIndex++) {
            array_shift($days_list);
            array_push($days_list, $weekdays[$weekHeIndex]);
        }

        // Create the table body rows
        $year_list = [];
        for ($j = 0; $j < 2; $j++) {
            $month_list = [];

            $orign_date = clone $start_date;
            $orign_date->modify("+$j months");

            $current_month = $orign_date->format('m');
            $current_year = $orign_date->format('Y');

            for ($i = 0; $i < 42; $i++) {
                $added_date = clone $orign_date;
                $added_date->modify("+$i days");

                if ($added_date->format('m') === $current_month && $added_date->format('Y') === $current_year) {
                    $month_list[] = $added_date->format("Y-m-d");
                } else {
                    $month_list[] = ""; // Replace dates from other months with an empty string
                }
            }

            $start_day_of_month = (int)$orign_date->format('w');  // Get the weekday of the 1st day of the month
            $week_diff = ($start_day_of_month - $start_week + 7) % 7;

            for ($i = 0; $i < $week_diff; $i++) {
                array_unshift($month_list, "");  // Add empty strings for padding before the first day of the month
                array_pop($month_list);  // Remove extra days at the end
            }

            $date_list = [];
            while (count($month_list) > 0) {
                array_push($date_list, array_splice($month_list, 0, 7));
            }
            array_push($year_list, $date_list);
        }
        return [
            "date_list" => $year_list,
            "days_list" => $days_list,
        ];
    }
}
