<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

define('DAY2COLUMN',
[
  'D','E','F','G','H','I','J','K','L','M',
  'N','O','P','Q','R','S','T','U','V','W',
  'X','Y','Z','AA','AB','AC','AD','AE','AF','AG',
  'AH',
]);

class ManPowerExport
{
    public static function export($rows, $month_date, $organization, $holiday)
    {
        $max_day = date('t', strtotime($month_date));

        $temp_file = base_path('template/manpower.xlsx');
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($temp_file);
        $sheet = $spreadsheet->getActiveSheet();

        $work_holiday = [];
        foreach ($holiday as $day) {
            $year_month_arr = explode("-", $day->holiday_date);
            array_push($work_holiday, $year_month_arr[2]);
        }

        for($i = 0, $size = count(DAY2COLUMN); $i < $size; ++$i) {
            // 日セルを埋める
            if ($i <= $max_day) {
                $sheet->setCellValue(DAY2COLUMN[$i] . 4 , (string)($i + 1));
            } else {
                $sheet->setCellValue(DAY2COLUMN[$i] . 4 , '');
            }

            $day = '';
            if ($i < 9) {
              $day = '0' . (string)($i+1);
            } else {
              $day = (string)($i+1);
            }

            // 祝日設定
            if (in_array($day, $work_holiday)) {
                $style = $sheet->getStyle(DAY2COLUMN[((int)$day) -1] . 4 . ':' . DAY2COLUMN[((int)$day)-1] . 28);
                $fill = $style->getFill();
                $fill->setFillType(Fill::FILL_SOLID);
                $fill->getStartColor()->setARGB('FFCCCCCC');
            }

            $date = date('w', strtotime($month_date . '/' . $day));
            // 土曜日と日曜日が対象
            if ($date == 0 || $date == 6) {
                $style = $sheet->getStyle(DAY2COLUMN[$i] . 4 . ':' . DAY2COLUMN[$i] . 28);
                $fill = $style->getFill();
                $fill->setFillType(Fill::FILL_SOLID);
                $fill->getStartColor()->setARGB('FFCCCCCC');
            }
        }

        // ユニークな現場(会社)を$setに、 $count_mapに日ごとの集計値を設定する
        $set = [];
        $count_map = [];

        foreach ($rows as $value) {
          $row = json_decode(json_encode($value), true);
          if ($row['supported_company_name'] != null) {
              $name = $row['location_name'] . '(' . $row['supported_company_name'] . ')';
              array_push($set, $name);
          } else if ($row['support_company_name'] != null) {
              $name = $row['location_name'] . '(' . $row['support_company_name'] . ')';
              array_push($set, $name);
          }

          $occupation = 0;
          if (false !== strpos($row['occupation_name'], '大工')) {
              $occupation = 0;
          } else if (false !== strpos($row['occupation_name'], '解体')) {
              $occupation = 1;
          } else if (false !== strpos($row['occupation_name'], '加工')) {
              $occupation = 2;
          } else {
              continue;
          }
          $count = 0;
          if ($row['detail_times'] == '1日') {
              $count = 1 * $row['dp_nums_of_people'];
          } else if ($row['detail_times'] == '午前' || $row['detail_times'] == '午後') {
              $count = 0.5 * $row['dp_nums_of_people'];
          } else {
              $count = 0.3 * $row['dp_nums_of_people'];
          }

          if (!array_key_exists($name, $count_map)) {
              $count_map[$name] = [];
          }

          $day = (int)explode("-", $row['target_date'])[2];
          if (!array_key_exists($day, $count_map[$name])) {
              $count_map[$name][$day] = array(0 => 0, 1 => 0, 2 => 0);
          }
          $count_map[$name][$day][$occupation] += $count;
        }

        $set = array_unique($set);
        $index = 5;
        foreach ($set as $company) {
            foreach ($count_map[$company] as $day => $row) {
              foreach ($row as $offset => $count) {
                if ($count != 0) {
                    $sheet->setCellValue(DAY2COLUMN[$day -1] . $index + $offset, $count);
                }
              }
            }
            $sheet->setCellValue("A" . $index, $company);
            $index += 3;
        }

        // ヘッダの直し
        $year_month_arr = explode("/", $month_date);

        $replace = str_replace('株式会社', '㈱', $organization);
        $sheet->setCellValue('A4', $replace . "分");

        $format =  "【%s】　　　　　　　　%d年%d月1日～%d年%d月%d日　　　　　　";
        $header = sprintf($format, $replace, $year_month_arr[0], $year_month_arr[1], $year_month_arr[0], $year_month_arr[1], $max_day);
        $sheet->setCellValue('A3', $header);

        // エクセル出力
        $writer = new Xlsx($spreadsheet);
        $format = '%d年%d月_%s_人工表.xlsx';
        $fname = sprintf($format, $year_month_arr[0], $year_month_arr[1], $organization);
        $writer->save('storage/' . $fname);
        return $fname;
    }
}
