<?php

namespace App\Http\Controllers\Admin\Report;

use App\Models\Organization;
use App\Http\Controllers\Controller;
use App\Models\DakouData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\WorkLocation;
use App\Models\Holiday;
use App\Helper\Helper;
use App\Http\Controllers\Admin\AttendanceRecord\AttendanceRecordManagementController;
use App\Http\Requests\DailyReportRequest;
use App\Http\Requests\MonthRequest;
use App\Models\AttendStatus;
use App\Models\BreakTime;
use App\Models\DakouChild;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TableManagementController extends Controller
{
    public $date_set_cols = [
        'D',
        'E',
        'F',
        'G',
        'H',
        'I',
        'J',
        'K',
        'L',
        'M',
        'N',
        'O',
        'P',
        'Q',
        'R',
        'S',
        'T',
        'U',
        'V',
        'W',
        'X',
        'Y',
        'Z',
        'AA',
        'AB',
        'AC',
        'AD',
        'AE',
        'AF',
        'AG',
        'AH',
    ];
    public $sheet_style;
    public $font_size;
    public $row_date_set_cols;

    public function __construct()
    {
        $this->sheet_style = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '000000']
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true
            ],
        ];
        $this->font_size = [
            'size' => 9,
            'name' => 'ＭＳ Ｐ明朝'
        ];
        $this->row_date_set_cols = [
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'W',
            'X',
            'Y',
            'Z',
            'AA',
            'AB',
            'AC',
            'AD',
            'AE',
            'AF',
            'AG',
            'AH',
            'AI',
            'AJ'
        ];
    }
    public function index()
    {
        $users         = User::with('user_data')->where('role', '!=', 1)->get();
        $locations     = WorkLocation::where("location_flag", 1)->get();
        $organizations = Organization::all();
        return Inertia::render('Admin/Report/Index', compact('users', 'locations', 'organizations'));
    }

    /**給与集計 */
    public function exportAttendData(MonthRequest $request)
    {
        $month_date     = $request->month;                                                   //Y/m
        $year_month_arr = explode("/", $month_date);
        $year           = $year_month_arr[0];
        $month          = $year_month_arr[1];
        $jp_year        = Helper::getJpDate($year, 'year');

        $break_data = BreakTime::with([
            'user_data.user.dakou.dakoku_children',
            'organization',
            'user_data.user.dakou.attend_status',
            'user_data.user.dakou' => function ($query) use ($year, $month) {
                $query->whereYear('target_date', $year);
                $query->whereMonth('target_date', $month);
            }
        ])->whereHas('user_data.user.dakou', function ($query) use ($year, $month) {
            $query->whereYear('target_date', $year);
            $query->whereMonth('target_date', $month);
        })->get();

        $map_data = $break_data->map(function ($item) use ($month_date) {
            // 勤務形態別にマッピング
            $work_type         = $item->break_work_pattern_cd . " ";
            $work_type        .= $item->organization->organization_name . " ";
            $work_type        .= $item->break_name;

            $user_data = $item->user_data; //Array
            $users_dakou_data = $user_data->map(function ($item) use ($month_date) {
                // 各ユーザーにマッピング
                $user_name  = $item->user->name;
                $dakou_data = $item->user->dakou;  // Array
                // そのユーザーの対応する月の打刻データをマッピングする
                // ・出勤日数 (日数でカウント)

                $normal_real_work_times  = 0;
                $holiday_real_work_times = 0;
                $record_controller       = app()->make(AttendanceRecordManagementController::class);
                $dakoku_by_user          = $record_controller->get_monthly_data($month_date . '/1', $item->user->id);
                foreach ($dakoku_by_user as $item) {
                    if ($item['holiday'] == '休日') {
                        $holiday_real_work_times += $item['work_time_seconds'] - $item['rest_seconds'];
                    } else {
                        $normal_real_work_times += $item['work_time_seconds'] - $item['rest_seconds'];
                    }
                }
                $normal_real_work_times = Helper::secondsToTime($normal_real_work_times);
                $holiday_real_work_times = Helper::secondsToTime($holiday_real_work_times);

                $normalday_attend_date_nums = 0;
                $holiday_attend_date_nums   = 0;
                // 日曜出勤
                $sunday_attend_count        = 0;
                // 平日休日残業時間 (「残業遅刻早退など」で選択した「1H残業」などの項目をカウント)
                $over_time   = 0;
                foreach ($dakou_data as $key => $val) {
                    $target_date = $val->target_date;
                    $is_holiday          = Holiday::whereDate('holiday_date', $target_date)->where('paid_holiday', 0)->exists();
                    $is_sunday = date('w', strtotime($target_date)) == 0;
                    if ($val->dp_other_flg) {
                        $other_flg_txt = AttendStatus::find($val->dp_other_flg)->attend_name;
                        preg_match('/[0-9.]+/', $other_flg_txt, $matches);
                        if (!empty($matches)) {
                            $other_count = $matches[0];
                            $over_time += $other_count * 3600;
                        }
                    }

                    if ($val->dp_type == '1日') {
                        if ($is_holiday) {
                            $holiday_attend_date_nums += 1;
                        } else {
                            $normalday_attend_date_nums += 1;
                        }

                        if($is_sunday) {
                            $sunday_attend_count += 1;
                        }
                    } else if ($val->dp_type == '半日') {
                        if ($is_holiday) {
                            $holiday_attend_date_nums += 0.5;
                        } else {
                            $normalday_attend_date_nums += 0.5;
                        }

                        if($is_sunday) {
                            $sunday_attend_count += 0.5;
                        }
                    }
                }
                $over_time = Helper::secondsToTime($over_time);
                // ・運転手手当 (打刻で運転を選択した回数)
                $drive_count = count($dakou_data->filter(function ($item) {
                    return $item->dp_ride_flg == '運転';
                }));
                $undrive_count = count($dakou_data->filter(function ($item) {
                    return $item->dp_ride_flg == '同乗';
                }));


                $paid_count = 0;
                foreach ($dakou_data as $val) {
                    if ($val['attend_status'] && $val['attend_status']['attend_name'] == '有給休暇') {
                        $paid_count++;
                    }
                }
                return [
                    'user_name'     => $user_name,
                    'n_workdays'    => $normalday_attend_date_nums,
                    'h_workdays'    => $holiday_attend_date_nums,
                    'n_worktimes'   => $normal_real_work_times,
                    'h_worktimes'   => $holiday_real_work_times,
                    'over_time'     => $over_time,
                    'sunday_count'  => $sunday_attend_count,
                    'drive_count'   => $drive_count,
                    'undrive_count' => $undrive_count,
                    'paid_count'    => $paid_count,
                ];
            })->toArray();
            return [
                'work_type'    => $work_type,
                'work_pattern' => $item?->break_work_pattern_cd,
                'data'         => $users_dakou_data
            ];
        })->toArray();

        if (count($map_data) == 0) {
            return response()->json(['success' => false, 'message' => 'データはありません。'], 200);
        }

        $temp_file = base_path('template/salary.xlsx');
        $spreadssheet = IOFactory::load($temp_file);

        // Step 1  勤怠・控除項目入力表
        $sheet1 = $spreadssheet->getAllSheets()[0];
        $sheet1->setCellValue('B1', $jp_year);
        $sheet1->setCellValue('B2', $month . '月分');

        $row = 0;
        foreach ($map_data as $item) {
            $sheet1->mergeCells('B' . ($row + 4) . ':J' . ($row + 4));
            $sheet1->setCellValue('B' . $row + 4, $item['work_type']);
            $sheet1->getRowDimension($row + 4)->setRowHeight(25);

            $this->sheet_style['font'] = $this->font_size;
            $sheet1->getStyle('B' . ($row + 4) . ':I' . ($row + 4))->applyFromArray($this->sheet_style);
            // writing header of user table
            $sheet1->setCellValue('B' . $row + 5, '月給者');
            if (strpos($item['work_pattern'], 'pa') !== false) {
                $sheet1->setCellValue('C' . $row + 5, "平日出勤\n時間");
                $sheet1->setCellValue('D' . $row + 5, "休日出勤\n時間");
            } else {
                $sheet1->setCellValue('C' . $row + 5, "平日出勤\n日数");
                $sheet1->setCellValue('D' . $row + 5, "休日出勤\n日数");
            }
            $sheet1->setCellValue('E' . $row + 5, "平日休日\n残業時間");
            $sheet1->setCellValue('F' . $row + 5, "日曜出勤");
            $sheet1->setCellValue('G' . $row + 5, "運転\n手手当");
            $sheet1->setCellValue('H' . $row + 5, "遠距離\n手当");
            $sheet1->setCellValue('I' . $row + 5, '有給');

            // Add wrap_text option
            $sheet1->getStyle('B' . ($row + 5) . ':I' . ($row + 5))->applyFromArray($this->sheet_style);
            $sheet1->getRowDimension($row + 5)->setRowHeight(30);

            foreach ($item['data'] as $key => $val) {
                $sheet1->setCellValue('B' . $row + 6, $val['user_name']);
                $sheet1->getStyle('B' . ($row + 6))->getAlignment()->setWrapText(true);

                if (strpos($item['work_pattern'], 'pa') !== false) {
                    $sheet1->setCellValue('C' . $row + 6, $val['n_worktimes']);
                    $sheet1->setCellValue('D' . $row + 6, $val['h_worktimes']);
                } else {
                    $sheet1->setCellValue('C' . $row + 6, $val['n_workdays']);
                    $sheet1->setCellValue('D' . $row + 6, $val['h_workdays']);
                }
                $sheet1->setCellValue('E' . $row + 6, $val['over_time']);
                $sheet1->setCellValue('F' . $row + 6, $val['sunday_count']);
                $sheet1->setCellValue('G' . $row + 6, $val['drive_count']);
                $sheet1->setCellValue('H' . $row + 6, $val['undrive_count']);
                $sheet1->setCellValue('I' . $row + 6, $val['paid_count']);
                $sheet1->getStyle('B' . ($row + 6) . ':I' . ($row + 6))->applyFromArray($this->sheet_style);
                $sheet1->getRowDimension($row + 6)->setRowHeight(25);
                $row++;
            }
            $row = $row + 3;
            // $skip_rows = $data_rows+5;
        }

        // Step2 ユーザー別給与集計
        $sheet2 = $spreadssheet->getAllSheets()[1];
        $attend_status_headers = AttendStatus::pluck('attend_name')->toArray();
        $sheet2->fromArray([$attend_status_headers], null, 'J1');
        // users's dakoku data/
        $dakoku_data = DakouData::with(['attend_status', 'user.user_data.break_times'])
            ->whereYear('target_date', $year)
            ->whereMonth('target_date', $month)
            ->orderBy('dp_user', 'asc')
            ->orderBy('target_date', 'asc')
            ->get()->toArray();
        foreach ($dakoku_data as $key => $val) {
            $sheet2->setCellValue('A' . $key + 2, Carbon::parse($val['target_date'])->format('n/j'));
            $sheet2->setCellValue('B' . $key + 2, Helper::getWeekday($val['target_date']));
            // 休日に合わせた色の変更
            $holidays = Holiday::whereDate('holiday_date', $val['target_date'])->where('paid_holiday', 0)->exists();
            $paid_holidays = Holiday::whereDate('holiday_date', $val['target_date'])->where('paid_holiday', 1)->exists();
            if ($val['target_date'] == '日' || $val['target_date'] == '土' || $holidays) {
                $sheet2->getStyle('B' . $key + 2)->getFont()->setColor(new Color(Color::COLOR_RED));
            }
            if ($paid_holidays) {
                $sheet2->getStyle('B' . $key + 2)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('CCFFFF00');
            }
            $sheet2->setCellValue('C' . $key + 2, $val['user']['name']);
            $sheet2->setCellValue('D' . $key + 2, $val['dp_syukkin_time'] ? Carbon::parse($val['dp_syukkin_time'])->format('H:i') : '');
            $sheet2->setCellValue('E' . $key + 2, $val['user']['user_data'] ? Carbon::parse($val['user']['user_data']['break_times']['break_start_time'])->format('H:i') : '');
            $sheet2->setCellValue('F' . $key + 2, $val['dp_taikin_time'] ? Carbon::parse($val['dp_taikin_time'])->format('H:i') : '');
            $sheet2->setCellValue('G' . $key + 2, $val['user']['user_data'] ? Carbon::parse($val['user']['user_data']['break_times']['break_end_time'])->format('H:i') : '');
            $sheet2->setCellValue('H' . $key + 2, $val['user']['code']);
            $sheet2->setCellValue('I' . $key + 2, $val['dp_type']);

            $other_status_id = $val['attend_status'] ? $val['attend_status']['id'] : null;
            $other_status = $val['attend_status'] ? $val['attend_status']['attend_name'] : "";
            if ($other_status_id) {
                $sheet2->setCellValueByColumnAndRow($other_status_id + 9, $key + 2, $other_status);
            }
        }

        $sheet2->getStyle('A2:AB' . count($dakoku_data) + 1)->applyFromArray($this->sheet_style);

        $new_excel = new Xlsx($spreadssheet);
        $public_path = public_path('storage/collecting');

        if (!file_exists($public_path)) {
            mkdir($public_path, 0777, true);
        }
        $file_name = sprintf('%d年%d月分給与集計.xlsx', $year, $month);
        $new_excel->save($public_path . '/' . $file_name);
        return response()->json(['success' => true, 'path' => 'collecting/' . $file_name]);
    }

    //**日報 */
    public function exportDailyData(DailyReportRequest $request)
    {
        $date         = $request->date;
        $user_id      = $request->user;
        $next_date    = Carbon::parse($date)->addDay()->format('Y-n-j');
        $jp_year      = Helper::getJpDate(date('Y', strtotime($date)), 'year');                 //A1
        $weekday      = Helper::getWeekday($date);                        // A3
        $name         = User::find($user_id)?->name;                      // M3 or M12

        // Mapping Data
        $date_arr = [$date, $next_date];
        $daily_data = [];

        foreach ($date_arr as $key => $date) {
            $oneday_data = DakouData::with([
                'user',
                'attend_status',
                'dakoku_children.support_company',
                'dakoku_children.supported_company',
                'dakoku_children.occupation',
                'dakoku_children.work_location',
                'dakoku_children.timezone',
                'dakoku_children.work_content',
            ])->where('dp_user', $user_id)->whereDate('target_date', $date)->first();

            if ($key > 0 && empty($oneday_data?->toArray())) continue;
            $child = $oneday_data?->dakoku_children;

            $child_arr = [];
            if (!empty($child?->toArray())) {
                foreach ($child as $val) {
                    $peoples = $val?->dp_nums_of_people;
                    if ($val->dp_support_flg == 1) {
                        $flag    = '応援に行った先';
                        $company = $val?->supported_company?->supported_company_name;
                        $peoples = $val?->dp_nums_of_people;
                    } else if ($val->dp_support_flg == 2) {
                        $flag = '応援来てもらった先';
                        $company = $val?->support_company?->support_company_name;
                    } else {
                        $flag    = 'なし';
                        $company = "";
                        $peoples = "";
                    }
                    $child_arr[] = [
                        'id'           => $val?->id,
                        'time_zone'    => $val?->timezone?->detail_times,
                        'occps'        => $val?->occupation?->occupation_name,
                        'location'     => $val?->work_location?->location_name,
                        'work_content' => $val?->work_content?->work_content_name,
                        'flag'         => $flag,
                        'company'      => $company,
                        'peoples'      => $peoples,
                    ];
                }
                usort($child_arr, function ($a, $b) {
                    return $a['id'] - $b['id'];
                });
            } else {
                $child_arr = [];
            }

            $weekday      = Helper::getWeekday($date);
            $other        = $oneday_data?->attend_status?->attend_name;
            $daily_data[] = [
                'date'    => date('n/j', strtotime($date)),
                'weekday' => $weekday,
                'name'    => $name,
                'type'    => $oneday_data?->dp_type,
                'other'   => $other,
                'absent'  => empty($oneday_data?->toArray()) ? "欠勤" : "",
                'late'    => strpos($other, '遅刻') !== false ? $other : "",
                'early'   => strpos($other, '早退') !== false ? $other : "",
                'memo'    => $oneday_data?->dp_memo,
                'child'   => $child_arr,
            ];
        }

        $spreadssheet = IOFactory::load(base_path('template/daily_export.xlsx'));
        $sheet = $spreadssheet->getActiveSheet();

        $sheet->setCellValue('A1', $jp_year);
        $skip_rows = 2;
        foreach ($daily_data as $key => $val) {
            $sheet->mergeCellsByColumnAndRow(1, $skip_rows, 3, $skip_rows);  // A:C
            $sheet->mergeCellsByColumnAndRow(8, $skip_rows, 9, $skip_rows); // H:I
            $sheet->mergeCellsByColumnAndRow(10, $skip_rows, 11, $skip_rows); // J:K
            $sheet->mergeCellsByColumnAndRow(13, $skip_rows, 16, $skip_rows); // M:P
            $sheet->getRowDimension($skip_rows)->setRowHeight(35);

            $sheet->setCellValue('A' . ($skip_rows), $val['date']);
            $sheet->setCellValue('D' . ($skip_rows), $val['weekday']);
            $sheet->setCellValue('E' . ($skip_rows), $val['type']);
            $sheet->setCellValue('G' . ($skip_rows), $val['absent']);
            $sheet->setCellValue('H' . ($skip_rows), $val['late']);
            $sheet->setCellValue('J' . ($skip_rows), $val['early']);
            $sheet->setCellValue('L' . ($skip_rows), "氏名");
            $sheet->setCellValue('M' . ($skip_rows), $val['name']);
            // adjusting row height

            // Writing Header of Child Section
            $sheet->mergeCellsByColumnAndRow(1, $skip_rows + 1, 2, $skip_rows + 1); // A:B
            $sheet->mergeCellsByColumnAndRow(5, $skip_rows + 1, 7, $skip_rows + 1); // E:G
            $sheet->mergeCellsByColumnAndRow(8, $skip_rows + 1, 11, $skip_rows + 1); // H:K
            $sheet->getRowDimension($skip_rows + 1)->setRowHeight(35);

            $sheet->setCellValue('A' . ($skip_rows + 1), "時間帯");
            $sheet->setCellValue('C' . ($skip_rows + 1), "工区");
            $sheet->setCellValue('D' . ($skip_rows + 1), "職種");
            $sheet->setCellValue('E' . ($skip_rows + 1), "現場名");
            $sheet->setCellValue('H' . ($skip_rows + 1), "作業内容");
            $sheet->setCellValue('L' . ($skip_rows + 1), "一緒に現場に\r入った従業員");
            $sheet->setCellValue('M' . ($skip_rows + 1), "応援区分");
            $sheet->setCellValue('N' . ($skip_rows + 1), "常用先会社名");
            $sheet->setCellValue('O' . ($skip_rows + 1), "人数");
            $sheet->setCellValue('P' . ($skip_rows + 1), "作業責任者名");
            // insert detail dakoku data
            $rows = 0;
            if (!empty($val['child'])) {
                foreach ($val['child'] as $row => $item) {
                    $sheet->mergeCellsByColumnAndRow(1, $skip_rows + $rows + 2, 2, $skip_rows + $rows + 2); // A:B
                    $sheet->mergeCellsByColumnAndRow(8, $skip_rows + $rows + 2, 11, $skip_rows + $rows + 2); // H:K
                    $sheet->mergeCellsByColumnAndRow(5, $skip_rows + $rows + 2, 7, $skip_rows + $rows + 2); // E:G
                    $sheet->getRowDimension($skip_rows + $rows + 2)->setRowHeight(40);

                    $sheet->setCellValue('A' . ($skip_rows + $rows + 2), $item['time_zone']);
                    $sheet->setCellValue('C' . ($skip_rows + $rows + 2), "");
                    $sheet->setCellValue('D' . ($skip_rows + $rows + 2), $item['occps']);
                    $sheet->setCellValue('E' . ($skip_rows + $rows + 2), $item['location']);
                    $sheet->setCellValue('H' . ($skip_rows + $rows + 2), $item['work_content']);
                    $sheet->setCellValue('L' . ($skip_rows + $rows + 2), "");
                    $sheet->setCellValue('M' . ($skip_rows + $rows + 2), $item['flag']);
                    $sheet->setCellValue('N' . ($skip_rows + $rows + 2), $item['company']);
                    $sheet->setCellValue('O' . ($skip_rows + $rows + 2), $item['peoples']);
                    $sheet->setCellValue('P' . ($skip_rows + $rows + 2), "");
                    $sheet->getStyle('M' . ($skip_rows + $rows + 2))->getAlignment()->setWrapText(true);
                    $rows++;
                }
            } else {
                $sheet->mergeCellsByColumnAndRow(1, $skip_rows  + $rows + 2, 16, $skip_rows + $rows + 2); // H:K
                $sheet->getRowDimension($skip_rows + $rows + 2 + $rows)->setRowHeight(40);
                $rows++;
            }
            $sheet->mergeCellsByColumnAndRow(1, $skip_rows + $rows + 2, 2, $skip_rows + $rows + 2); // A:B
            $sheet->mergeCellsByColumnAndRow(3, $skip_rows + $rows + 2, 12, $skip_rows + $rows + 2); // A:B
            $sheet->mergeCellsByColumnAndRow(14, $skip_rows + $rows + 2, 16, $skip_rows + $rows + 2); // A:B
            $sheet->getRowDimension($skip_rows + $rows + 2)->setRowHeight(35);

            $sheet->setCellValue('A' . ($skip_rows + $rows + 2), "備考");
            $sheet->setCellValue('C' . ($skip_rows + $rows + 2), $val['memo']);
            $sheet->setCellValue('M' . ($skip_rows + $rows + 2), "残業");
            $sheet->setCellValue('N' . ($skip_rows + $rows + 2), $val['other']);
            $skip_rows += $rows + 4;
        }
        $this->sheet_style['font'] = [
            'size' => 10
        ];
        $sheet->getStyle('A2:P' . $skip_rows - 2)->applyFromArray($this->sheet_style);

        $sheet->setTitle("日報");
        $new_excel = new Xlsx($spreadssheet);
        $file_name = sprintf("%s%s_%s_さんの日報.xlsx", $jp_year, date("Y-n", strtotime($date)), $name);
        $new_excel->save('storage/' . $file_name);
        return response()->json(['success' => true, 'path' => $file_name]);
    }

    /**出勤簿帳票 */
    public function exportAttendanceBook(MonthRequest $request)
    {
        $month_date     = $request->month;
        $year_month_arr = explode("/", $month_date);
        $year           = $year_month_arr[0];
        $month          = $year_month_arr[1];

        $max_day = date('t', strtotime($month_date));
        $locations = WorkLocation::where("location_flag", 1)->get();
        $locations = $locations->collect()->map(function ($location) {
            return [
                'id'    => $location->id,
                'name'  => $location?->location_name,
                'count' => 0
            ];
        });
        $locations_id_arr = WorkLocation::where('location_flag', 1)->pluck('id');

        $users = User::with([
            'dakou' => function ($query) use ($year, $month) {
                $query->whereYear('target_date', $year);
                $query->whereMonth('target_date', $month);
            },
            'dakou.dakoku_children' => function ($query) use ($locations_id_arr) {
                $query->whereIn('dp_work_location_id', $locations_id_arr);
            },
            'user_data.break_times.organization'
        ])
            ->where('role', '!=', 1)
            ->whereHas('dakou', function ($q) use ($year, $month, $locations_id_arr) {
                $q->whereYear('target_date', $year);
                $q->whereMonth('target_date', $month);
                $q->whereHas('dakoku_children', function ($sq) use ($locations_id_arr) {
                    $sq->whereIn('dp_work_location_id', $locations_id_arr);
                });
            })->get();

        $users = $users->collect()->map(function ($user) use ($locations) {
            $org = $user?->user_data?->break_times?->organization?->organization_name;
            if (mb_strpos($org, '株式会社') !== false) {
                $org = str_replace("株式会社", "", $org);
            }
            return [
                'user_id'   => $user->id,
                'user_name' => $user->name,
                'org'       => $org ?? "NULL",
                'location'  => $locations,
            ];
        })->toArray();

        $users = array_reduce($users, function ($acc, $cur) {
            if (!isset($acc[$cur['org']])) {
                $acc[$cur['org']] = [
                    'org' => $cur['org'],
                    'users' => []
                ];
            }
            $acc[$cur['org']]['users'][] = [
                'user_id' => $cur['user_id'],
                'user_name' => $cur['user_name'],
                'location' => $cur['location']
            ];
            return $acc;
        }, []);

        $users = array_values($users);

        $row_data = [];
        foreach ($users as $keys => $user) {
            $sub_sum = [];
            foreach ($user['users'] as $org_key => $orval) {
                foreach ($orval['location'] as $key => $location) {
                    $count = DakouData::with(['dakoku_children.occupation'])->where('dp_user', $orval['user_id'])
                        ->whereYear('target_date', $year)
                        ->whereMonth('target_date', $month)
                        ->whereHas('dakoku_children', function ($q) use ($location) {
                            $q->where('dp_work_location_id', $location['id']);
                            $q->whereHas('occupation', function ($sq) {
                                $sq->where('occupation_name', 'like', '%現場%');
                            });
                        })->count();
                    $row_data[$user['org']]['user'][$org_key]['user_name'] = $orval['user_name'];
                    $row_data[$user['org']]['user'][$org_key]['locations'][$key] = $count;
                }
                $loc_counts_arr = $row_data[$user['org']]['user'][$org_key]['locations'];
                foreach ($loc_counts_arr as $k => $v) {
                    if (!isset($sub_sum[$k])) {
                        $sub_sum[$k] = 0;
                    }
                    $sub_sum[$k] += $v;
                }
            }
            $row_data[$user['org']]['sub_sums'] = $sub_sum;
        }

        if (count($row_data) == 0) {
            return response()->json(['success' => false, 'message' => 'データはありません。'], 200);
        }

        $temp_file   = base_path('template/attend_book.xlsx');
        $spreadsheet = IOFactory::load($temp_file);
        $sheet       = $spreadsheet->getActiveSheet();

        $formatted = "勤労状況     %s%d月1日　～　%s%d月%d日";
        $jp_year   = Helper::getJpDate($year, 'year');
        $header    = sprintf($formatted, $jp_year, $month, $jp_year, $month, $max_day);

        /**
         * 現場名の追加、削除があるので動的にコラム幅を調整する必要がある 
         * 最小12列, 最小幅 9.5*12 = 114
         * 印刷時のレイアウトの崩れを防ぐため、1列の幅は 114 / 現場名
         */

        $sheet->setCellValue('A2', "出 勤 簿 帳 票");
        $sheet->setCellValue('D3', $header);

        // 現場数だけ併合、12より小さい時は12カラム併合
        $nums_of_locations = count($locations);
        $location_column_width = round(114 / $nums_of_locations);
        if ($nums_of_locations > 12) {
            $sheet->mergeCellsByColumnAndRow(4, 3, $nums_of_locations + 3, 3); // start col, start row, end col, end row
            $sheet->mergeCellsByColumnAndRow(1, 2, $nums_of_locations + 4, 2); // A2:P2

            // 最後の列は合計 // $nums_of_locations+4, not 3
            // ユーザー別合計列のマージとデータの書き込み
            $sheet->mergeCellsByColumnAndRow($nums_of_locations + 4, 3, $nums_of_locations + 4, 4); // P col
            $sheet->setCellValueByColumnAndRow($nums_of_locations + 4, 3, '合計');
            $sheet->getColumnDimensionByColumn($nums_of_locations + 4)->setWidth(6);
        } else {
            $sheet->mergeCellsByColumnAndRow(4, 3, 15, 3); // 14 N column / Locations
            $sheet->mergeCellsByColumnAndRow(1, 2, 16, 2); // 15 O column Header
            $sheet->mergecells('O3:O4');
            $sheet->setCellValue('O3', '合計');
            $sheet->getColumnDimension('O')->setWidth(6);
        }

        // 4行、現場名記入 D4~
        foreach ($locations as $key => $location) {
            $col = $key + 4;
            $row = 4;
            $sheet->setCellValueByColumnAndRow($col, $row, $location['name']);
            // adjust cell width
            $sheet->getColumnDimensionByColumn($key + 4)->setWidth($location_column_width);
            $sheet->getRowDimension(4)->setRowHeight(-1); //auto fit height
            $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setWrapText(true);
        }

        $sum_by_location_arr = []; // 現場別カウント

        $rowIndex = 0;
        $noIndex = 0;
        foreach ($row_data as $org => $orgval) {
            $sheet->setCellValue('B' . $rowIndex + 5, $org == "NULL" ? "未組織" : $org);
            $sheet->mergeCells('B' . ($rowIndex + 5) . ':B' . (count($orgval['user']) + $rowIndex + 4));
            foreach ($orgval['user'] as $user) {
                $sheet->setCellValue('A' . $rowIndex + 5, $noIndex + 1);
                $sheet->setCellValue('C' . $rowIndex + 5, $user['user_name']);
                $sheet->getRowDimension($rowIndex + 5)->setRowHeight(-1);

                foreach ($user['locations'] as $col_key => $val) {
                    // From 'C5 col' in other words row 5, col 3
                    $col = $col_key + 4;
                    $row = $rowIndex + 5;
                    if ($val > 0) {
                        $sheet->setCellValueByColumnAndRow($col, $row, $val);
                    }

                    // 現場別カウント 最後のユーザーの下の行に入力
                    if (!isset($sum_by_location_arr[$col_key])) {
                        $sum_by_location_arr[$col_key] = $val;
                    } else {
                        $sum_by_location_arr[$col_key] += $val;
                    }
                }

                // ユーザー別合計
                if ($nums_of_locations > 12) {
                    $sheet->setCellValueByColumnAndRow($nums_of_locations + 4,  $rowIndex + 5,  array_sum($user['locations']));
                } else {
                    $sheet->setCellValue('P' . $rowIndex + 5,  array_sum($user['locations']));
                }
                $rowIndex++;
                $noIndex++;
            }
            // 小計
            $sheet->setCellValue('A' . $rowIndex + 5, '小計');
            $sheet->mergeCells('A' . ($rowIndex + 5) . ':C' . ($rowIndex + 5));
            $sheet->fromArray([$orgval['sub_sums']], null, 'D' .  $rowIndex + 5);
            $sheet->setCellValueByColumnAndRow($nums_of_locations + 4, $rowIndex + 5, array_sum($orgval['sub_sums']));
            $sheet->getStyleByColumnAndRow(1, $rowIndex + 5, $nums_of_locations + 4, $rowIndex + 5)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
            $rowIndex++;
        }

        // 現場別合計
        $sheet->mergeCells('A' . ($rowIndex + 5) . ':C' . ($rowIndex + 5));
        $sheet->setCellValue('A' . $rowIndex + 5, '合計');
        $sheet->fromArray([$sum_by_location_arr], null, 'D' .  $rowIndex + 5);

        $sheet->setCellValueByColumnAndRow($nums_of_locations + 4, $rowIndex + 5, array_sum($sum_by_location_arr));
        $sheet->getStyleByColumnAndRow(1, $rowIndex + 5, $nums_of_locations + 4, $rowIndex + 5)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD0D0D0');


        // スタイル適用 A2 ~ 
        $sheet->getStyleByColumnAndRow(1, 2, $nums_of_locations + 4, $rowIndex + 5)->applyFromArray($this->sheet_style);

        $writer = new Xlsx($spreadsheet);
        $format = '%s%d月_出勤簿帳票.xlsx';
        $fname = sprintf($format, $jp_year, $month);
        $writer->save('storage/' . $fname);

        return response()->json(['success' => true, 'path' => $fname]);
    }

    public function exportDriverData(MonthRequest $request)
    {
        $month_date     = $request->month;            // Y/n 2024/1
        $year_month_arr = explode("/", $month_date);
        $year           = $year_month_arr[0];
        $month          = $year_month_arr[1];
        $max_day = date("t", strtotime($month_date));
        // Mapping the data

        $users = User::join('dakou_data', 'dakou_data.dp_user', '=', 'users.id')
            ->whereYear('dakou_data.target_date', $year)
            ->whereMonth('dakou_data.target_date', $month)
            ->select('users.id as user_id', 'users.name as user_name', 'target_date as date', 'dp_ride_flg as ride')
            ->orderBy('dakou_data.target_date')
            ->get()->groupBy('user_id');

        if (count($users) == 0) {
            // データがない場合には、出力しません。
            return response()->json(['success' => false, 'message' => 'データはありません。'], 200);
        }

        $data = [];
        $users = array_values($users->toArray()); // reassign id from 0

        foreach ($users as $keys => $user) {
            $data[$keys]['user'] = $user[0]['user_name'];
            foreach ($user as $key => $val) {
                // 日付をキーにマッピング
                $date_key = date('j', strtotime($val['date']));
                if ($val['ride'] == '運転') {
                    $data[$keys]['cells'][$date_key] = 1;
                } else if ($val['ride'] == '同乗') {
                    $data[$keys]['cells'][$date_key] = 2;
                }
            }
        }

        $temp_file = base_path('template/driver.xlsx');
        $spreadsheet = IOFactory::load($temp_file);
        $sheet = $spreadsheet->getSheetByName("original");
        $sheet->setTitle($month . "月_1");

        // A, B col 日付と曜日の入力
        $date_set_rows = range(1, $max_day);
        foreach ($date_set_rows as $key => $date) {
            $sheet->setCellValue('A' . $key + 4, $date);
            $sheet->setCellValue('B' . $key + 4, Helper::getWeekday($month_date . '/' . $date));

            $day = date('w', strtotime($month_date . '/' . $date));
            if (in_array($day, [0, 6])) {
                $style = $sheet->getStyle('A' . ($key + 4) . ':B' . $key + 4);
                $style->getFont()->setColor(new Color(Color::COLOR_RED));
            }
        }

        $formatted = "%s%d月_ナナキ_運転手手当・同乗者手当";
        $jp_year = Helper::getJpDate($year, 'year');
        $header = sprintf($formatted, $jp_year, $month);
        $sheet->setCellValue('A1', $header);
        $sheet->setCellValue('A2', $month . "月");


        // ユーザー数が20人を超える場合、追加するシート数を計算
        $max_sheet_nums = ceil(count($data) / 20);
        for ($sheet_num = 1; $sheet_num < (int)$max_sheet_nums; $sheet_num++) {
            // ユーザーが20人以上の場合は新規シートを追加（熱を増やすと印刷範囲を外れるため）
            $origin_sheet = $spreadsheet->getSheetByName($month . "月_1");
            $newSheet = clone $origin_sheet;
            $newSheet->setTitle($month . "月_" . $sheet_num + 1);
            $spreadsheet->addSheet($newSheet);
        }

        // 行、列別にデータを入力する
        for ($sheet_num = 1; $sheet_num <= (int)$max_sheet_nums; $sheet_num++) {
            // 20行を超える場合、アクティブシートを次のシートに変更 $key -> 0~19
            $sheet = $spreadsheet->getSheetByName($month . "月_" . (int)$sheet_num);
            foreach ($data as $key => $cols) {
                // 2番目のシートに21番目のユーザーデータから入力
                // シート1では0～19、2では20～39のデータのみ入力
                if ($key + 1 > ($sheet_num - 1) * 20 && $key < $sheet_num * 20) {
                    // $key%20 を使う理由は、新しいシートを作成したときに C 列からの入力が必要なためだ。
                    $sheet->setCellValueByColumnAndRow(($key % 20) * 2 + 3, 2, $cols['user']);
                    if (isset($cols['cells']) && count($cols['cells'])) {
                        foreach ($cols['cells'] as $date_key => $val) {
                            if ($val == 1) {
                                // 運転
                                $col_num = 2 * ($key % 20) + 3;
                            } else {
                                // 同乗
                                $col_num = 2 * ($key % 20) + 4;
                            }
                            $row_num = $date_key + 3; //date key 1~31; from A4
                            $sheet->setCellValueByColumnAndRow($col_num, $row_num, '●');
                        }
                        // 合計
                        // 運転合計
                        $driving_arr = array_filter($cols['cells'], function ($item) {
                            return $item == 1;
                        });
                        $driving_sums = count($driving_arr);
                        // 同乗合計
                        $riding_arr = array_filter($cols['cells'], function ($item) {
                            return $item == 2;
                        });
                        $riding_sums = count($riding_arr);
                        $sheet->setCellValueByColumnAndRow(($key % 20) * 2 + 3, 35, $driving_sums);
                        $sheet->setCellValueByColumnAndRow(($key % 20) * 2 + 4, 35, $riding_sums);
                    }
                }
            }
        }

        // config sheet name
        $fname  = $year . "年" . $month . "月_運転手手当・同乗者手当一覧_帳票.xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save('storage/' . $fname);
        return response()->json(['success' => true, 'path' => $fname]);
    }
    /**現場別出勤表 */
    public function exportAttendPerLocationData(Request $request)
    {
        $request->validate(
            [
                'month'    => 'required',
                'location' => 'required',
            ],
            [
                'month.required'    => '対象月を選択してください。',
                'location.required' => '現場を選択してください。',
            ]
        );
        $month_date     = $request->month;                                             // Y/n/j 2024/1/1
        $location       = $request->location;                                          // Location ID
        $year_month_arr = explode("/", $month_date);
        $year           = $year_month_arr[0];
        $month          = $year_month_arr[1];
        $jp_year        = Helper::getjpDate($year, 'year');
        $data           = [];
        $max_day        = date('t', strtotime($month_date));
        $users          = User::where('role', '!=', 1)->where('available', 1)->get();
        $location_name  = WorkLocation::find($location)?->location_name;
        // Mapping the data to insert excel
        foreach ($users as $key => $user) {
            $data[] = [
                'user_name' => $user->name,
                'attend_data' => []
            ];
            $i = 1;
            while ($i <= $max_day) {
                $dakoku_data = DakouData::query()
                    ->join('dakou_children', 'dakou_data.id', '=', 'dakou_children.dp_dakoku_id')
                    ->join('work_locations', 'dakou_children.dp_work_location_id', '=', 'work_locations.id')
                    ->join('occupations', 'dakou_children.dp_occupation_id', '=', 'occupations.id')
                    ->where('dp_user', $user->id)
                    ->whereDate('target_Date', Carbon::parse($month_date)->format('Y/n') . '/' . $i)
                    ->where('dp_work_location_id', $location)
                    ->where('occupation_name', 'like', "%現場%")
                    ->exists();
                // $dakoku_data = DakouData::with([
                //     'dakoku_children.work_location' => function ($query) use ($location) {
                //         $query->where('id', $location);
                //     },
                //     'dakoku_children.occupation' => function ($query)  {
                //         $query->where('occupation_name', 'like', "%現場%");
                //     },

                // ])->where('dp_user', $user->id)
                //     ->whereDate('target_date', Carbon::parse($month_date)->format('Y/n') . '/' . $i)
                //     ->whereHas('dakoku_children.work_location', function ($query) use ($location) {
                //         $query->where('id', $location);
                //     })->whereHas('dakoku_children.occupation', function ($query) {
                //         $query->where('occupation_name', 'like', "%現場%");
                //     })->exists();
                if ($dakoku_data) {
                    $data[$key]['attend_data'][] = $i;
                }
                $i++;
            }
        }

        // Config Excel
        $temp_file = base_path('template/attend.xlsx');
        $spreadsheet = IOFactory::load($temp_file);
        $sheet = $spreadsheet->getActiveSheet();

        $format = '勤労状況　　　　　　　　　%s%d月1日　～　%d月%d日';
        $header = sprintf($format, $jp_year, $month, $month, $max_day);
        $sheet->setCellValue('B2', sprintf("（%s現場）", $location_name));
        $sheet->setCellValue('D3', $header);

        $date_set_arrs = range(1, $max_day);

        $sum_by_date  = []; //AI COLUMN VALUE
        $sheet->fromArray([$date_set_arrs], null, 'D4');
        $rows = 0;
        foreach ($data as $key => $val) {
            $sheet->setCellValue('A' . $key + 5, $key + 1);
            $sheet->setCellValue('C' . $key + 5, $val['user_name']);
            $sheet->setCellValue('AI' . $key + 5, count($val['attend_data']) > 0 ? count($val['attend_data']) : "");

            $sheet->getStyle('C' . $key + 5)->getAlignment()->setWrapText(true);
            $sheet->getRowDimension($key + 5)->setRowHeight(-1);
            foreach ($val['attend_data'] as $item) {
                $sheet->setCellValue($this->date_set_cols[$item - 1] . ($key + 5), '/');
                $sheet->getRowDimension($key + 5)->setRowHeight(-1);
                if (!isset($sum_by_date[$item - 1])) {
                    $sum_by_date[$item - 1] = 1;
                } else {
                    $sum_by_date[$item - 1]++;
                }
            }
            $rows++;
        }
        $sheet->mergeCells('A' . ($rows + 5) . ':C' . ($rows + 5));
        $sheet->setCellValue('A' . ($rows + 5), '合計');
        $sheet->setCellValue('AI' . ($rows + 5), array_sum($sum_by_date));
        $sheet->getStyle('A5:AI' . count($data) + 5)->getFont()->setSize(11);
        foreach ($sum_by_date as $key => $val) {
            $sheet->setCellValue($this->date_set_cols[$key] . $rows + 5, $val);
        }
        $sheet->getStyle('A5:AI' . $rows + 5)->applyFromArray($this->sheet_style);


        $new_excel = new Xlsx($spreadsheet);
        $f_file_name = '%d年%d月_%s_出勤表.xlsx';
        $file_name = sprintf($f_file_name, $year, $month, $location_name);

        $file_path = public_path('storage/workplace');
        if (!file_exists($file_path)) {
            mkdir($file_path, 0777, true);
        }
        $new_excel->save($file_path . '/' . $file_name);
        return response()->json(['success' => true, 'path' => 'workplace/' . $file_name]);
    }

    /**組織別人工表 */
    public function exportManPowerData(Request $request)
    {
        $request->validate(
            [
                'month'        => 'required',
                'organization' => 'required|array',
            ],
            [
                'month.required'        => '対象月を選択してください。',
                'organization.required' => '組織を選択してください。',
            ]
        );
        $month_date     = $request->month;                                                                                      //Y/n
        $organization   = $request->organization;
        $year           = explode('/', $month_date)[0];
        $month          = explode('/', $month_date)[1];
        $work_locations = WorkLocation::join('dakou_children', 'dakou_children.dp_work_location_id', '=', 'work_locations.id')
            ->join('dakou_data', 'dakou_data.id', '=', 'dakou_children.dp_dakoku_id')
            ->join('users', 'users.id', '=', 'dakou_data.dp_user')
            ->join('user_data', 'user_data.user_id', '=', 'users.id')
            ->join('break_times', 'break_times.id', '=', 'user_data.work_pattern_code_id')
            ->join('organizations', 'organizations.id', '=', 'break_times.break_organization')
            ->join('occupations', 'occupations.id', '=', 'dakou_children.dp_occupation_id')
            ->join('time_zones', 'time_zones.id', '=', 'dakou_children.dp_timezone_id')
            ->whereYear('dakou_data.target_date', $year)
            ->whereMonth('dakou_data.target_date', $month)
            ->where('organizations.id', $organization['id'])
            ->whereIn('dakou_children.dp_occupation_id', [1, 2, 3])
            ->where('dakou_data.deleted_at', null)
            ->where('dakou_children.deleted_at', null)
            ->select('users.name', 'work_locations.id as locationId', 'work_locations.location_name', 'dakou_data.target_date as date', 'occupations.id as occupation', 'time_zones.id as time', 'organizations.organization_name as organization')
            ->orderBy('locationId', 'asc')
            ->get()->groupBy('locationId');
        if (count($work_locations) == 0) {
            return response()->json(['success' => false]);
        }

        // 小計
        $sub_total_array = [
            'location' => '小計',
            'cols' => [[], [], []]
        ];

        // 合　計
        $total_array_rows = [];

        $max_day = date('t', strtotime($month_date));
        $rows = $work_locations->collect()->map(function ($item) use ($max_day, $year, $month, &$sub_total_array, &$total_array_rows) {
            // $rows divide by organization data
            $i = 1;
            $occp_rows = [[], [], []];
            while ($i <= $max_day) {
                foreach ($occp_rows as $keys => $row) {
                    $cell_data = $item->collect()->filter(function ($val) use ($year, $month, $i, $keys) {
                        return  $val['occupation'] == $keys + 1 && Carbon::parse($val['date'])->eq(Carbon::createFromFormat('Y-m-d', $year . '-' . $month . '-' . $i)->format('Y-m-d'));
                    });

                    if (!empty($cell_data->toArray())) {
                        $count = 0;
                        foreach ($cell_data as $key => $cell) {
                            $timezone = $cell['time'];
                            if ($timezone == 1) {
                                $count += 1;
                            } else if (in_array($timezone, [2, 3])) {
                                $count += 0.5;
                            } else {
                                $count += 0.3;
                            }
                        }
                        $occp_rows[$keys][$i] = $count;

                        if (!isset($sub_total_array['cols'][$keys][$i])) {
                            $sub_total_array['cols'][$keys][$i] = 0;
                        }
                        $sub_total_array['cols'][$keys][$i] += $count;

                        if (!isset($total_array_rows[$i])) {
                            $total_array_rows[$i] = 0;
                        }
                        $total_array_rows[$i] += $count;
                    }
                }
                $i++;
            }

            return [
                'location' => $item[0]['location_name'],
                'cols'     => $occp_rows,
            ];
        });

        // 小計
        foreach ($sub_total_array['cols'] as $key => $val) {
            ksort($sub_total_array['cols'][$key]);
        }

        $rows = $rows->toArray();
        array_push($rows, $sub_total_array);

        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'データはありません。'], 200);
        }
        // 現場順に並べ替えてキー再割り当て
        ksort($rows);
        $rows = array_values($rows);

        $holidays = Holiday::whereYear('holiday_date', $year)
            ->whereMonth('holiday_date', $month)
            ->where('paid_holiday', 0)
            ->pluck('holiday_date');

        $temp_file   = base_path('template/manpower.xlsx');
        $spreadsheet = IOFactory::load($temp_file);
        $sheet       = $spreadsheet->getActiveSheet();

        $replace = str_replace('株式会社', '㈱', $organization['organization_name']);
        $sheet->setCellValue('A4', $replace . "分");
        $sheet->setCellValue('A3', $replace . "分");
        $jp_year = Helper::getJpDate($year, 'year');                                //A1
        $format  = "%s%d月1日～%s%d月%d日";
        $header  = sprintf($format, $jp_year, $month, $jp_year, $month, $max_day);
        $sheet->setCellValue('B3', $header);

        $date_range = range(1, $max_day);
        $sheet->fromArray([$date_range], null, 'D4');

        $c_col_txt = ['大工', '解体', '加工'];

        $skip_rows = 0;
        $rowIndex = 0;
        foreach ($rows as $r_key => $row) {
            $large_row_key = $r_key * 3;
            $sheet->setCellValue('A' . $large_row_key + 5, $row['location']);
            $sheet->mergeCells('A' . ($large_row_key + 5) . ':A' . ($large_row_key + 7));
            foreach ($row['cols'] as $sub_key => $sub_row) {
                $sheet->setCellValue('B' . $large_row_key + $sub_key + 5, $c_col_txt[$sub_key]);
                $sheet->setCellValue('C' . $large_row_key + $sub_key + 5, array_sum($sub_row));
                foreach ($sub_row as $col_key => $val) {
                    $sheet->setCellValue($this->date_set_cols[$col_key - 1] . $large_row_key + $sub_key + 5, $val);
                }
                $sheet->getStyle('A' . $rowIndex + 5)->getAlignment()->setWrapText(true);
                $sheet->getRowDimension($rowIndex + 5)->setRowHeight(-1);

                // 小計 style
                if ($r_key + 1 == count($rows)) {
                    $sheet->getStyle('A' . ($large_row_key + 5) . ':AH' . ($large_row_key + 8))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEBE834');
                }

                $rowIndex++;
            }
            $skip_rows++;
        }


        $last_rows = count($rows) > 1 ? count($rows) * 3 + 5 : 10;
        // 現場別総合計
        $sheet->mergeCells('A' . $last_rows . ':B' . $last_rows);
        $sheet->setCellValue('A' . $last_rows, '合　計');
        $sheet->setCellValue('C' . $last_rows, array_sum($total_array_rows));

        foreach ($total_array_rows as $date_key => $sum_val) {
            $sheet->setCellValue($this->date_set_cols[$date_key - 1] . $last_rows, $sum_val);
        }

        // 祝日設定
        $days_of_holidays = $holidays->collect()->map(function ($val) {
            return Carbon::parse($val)->format('j');
        });

        // Border Style and Font
        $this->sheet_style['font'] = ['size' => 12];
        $sheet->getStyle('A6:AH' . $last_rows)->applyFromArray($this->sheet_style);
        foreach ($days_of_holidays as $day) {
            $style = $sheet->getStyle($this->date_set_cols[$day - 1] . '4:' . $this->date_set_cols[$day - 1] . $last_rows);
            $fill  = $style->getFill();
            $fill->setFillType(Fill::FILL_SOLID);
            $fill->getStartColor()->setARGB('FFCCCCCC');
        }
        // 土曜日と日曜日が対象
        foreach (range(1, $max_day) as $day) {
            $week = date('w', strtotime($month_date . '/' . $day));
            if ($week == 0 || $week == 6) {
                $style = $sheet->getStyle($this->date_set_cols[$day - 1] . '4:' . $this->date_set_cols[$day - 1] . $last_rows);
                $fill  = $style->getFill();
                $fill->setFillType(Fill::FILL_SOLID);
                $fill->getStartColor()->setARGB('FFCCCCCC');
            }
        }

        $writer = new Xlsx($spreadsheet);
        $format = '%s%d月_%s_人工表.xlsx';
        $fname = sprintf($format, $jp_year, $month, $organization['organization_name']);
        $writer->save('storage/' . $fname);

        return response()->json(['success' => true, 'path' => $fname]);
    }
    public function exportSupportFlgData(Request $request)
    {
        $rules = ['month' => 'required'];
        $msg   = ['month.required' => '対象月を選択してください。'];
        $all_visible = $request->input('allVisible');
        if (!$all_visible) {
            $rules['organization']        = 'required|array';
            $msg['organization.required'] = '組織を選択してください。';
        }
        $request->validate($rules, $msg);
        ini_set('max_execution_time', 600);

        $month_date   = $request->input('month');
        $organization = $request->input('organization');
        $type         = $request->input('type');
        $year         = explode('/', $month_date)[0];
        $month        = explode('/', $month_date)[1];
        $max_day      = date('t', strtotime($month_date));
        $jp_year      = Helper::getJpDate($month_date, 'year');
        $holidays     = Holiday::whereYear('holiday_date', $year)
            ->whereMonth('holiday_date', $month)
            ->where('paid_holiday', 0)
            ->pluck('holiday_date')->toArray();
        $paid_holidays  = Holiday::whereYear('holiday_date', $year)
            ->whereMonth('holiday_date', $month)
            ->where('paid_holiday', 1)
            ->pluck('holiday_date')->toArray();
        $date_range        = range(1, $max_day);

        $date_arr = collect($date_range)->map(function ($date) use ($holidays, $paid_holidays, $year, $month) {
            $formated_date = Carbon::create($year, $month, $date)->format('Y-m-d');
            return  [
                'date'            => $formated_date,
                'week_day'        => Helper::getWeekday($formated_date),
                'is_holiday'      => in_array($formated_date, $holidays),
                'is_paid_holiday' => in_array($formated_date, $paid_holidays),
                'sum_by_date'     => 0,
            ];
        })->toArray();
        $title             = sprintf("%s　%d月1日～%d日", $jp_year, $month, $max_day);

        $sheet_data   = [];
        if ($all_visible) {
            $org_arr = Organization::all();
        } else {
            $org_arr = [$organization];
        }
        foreach ($org_arr as $key => $org_val) {
            $dakoku_data = DakouChild::with(['dakoku', 'support_company', 'supported_company', 'occupation', 'work_location', 'dakoku.user.user_data.break_times.organization'])
                ->whereHas('dakoku', function ($query) use ($year, $month, $org_val) {
                    $query->whereYear('target_date', $year);
                    $query->whereMonth('target_date', $month);
                    $query->whereHas('user.user_data.break_times.organization', function ($q) use ($org_val) {
                        $q->where('id', $org_val['id']);
                    });
                })->where('dp_support_flg', $type)->where('dp_nums_of_people', '!=', null)->get();
            foreach ($dakoku_data as $val) {
                $comp_id = $type == 1 ? $val->dp_supported_company_id : $val->dp_support_company_id;
                if (!$comp_id) {
                    continue;
                }
                $location_id = $val->dp_work_location_id;
                $occupation_id = $val->dp_occupation_id;

                if (!isset($sheet_data[$org_val['id']])) {
                    $sheet_data[$org_val['id']] = [
                        'org_id'    => $org_val['id'],
                        'org_name'  => $org_val['organization_name'],
                        'comp_data' => []
                    ];
                }
                if (!isset($sheet_data[$org_val['id']]['comp_data'][$comp_id])) {
                    $comp_name = $type == 1 ? $val->supported_company?->supported_company_name : $val->support_company?->support_company_name;
                    $sheet_data[$org_val['id']]['comp_data'][$comp_id] = [
                        'comp_id'   => $comp_id,
                        'comp_name' => $comp_name,
                        'location'  => []
                    ];
                }

                if (!isset($sheet_data[$org_val['id']]['comp_data'][$comp_id]['location'][$location_id])) {
                    $sheet_data[$org_val['id']]['comp_data'][$comp_id]['location'][$location_id] = [
                        'loc_id'   => $location_id,
                        'loc_name' => $val->work_location?->location_name,
                        'occps'    => []
                    ];
                }

                if (!isset($sheet_data[$org_val['id']]['comp_data'][$comp_id]['location'][$location_id]['occps'][$occupation_id])) {
                    $row_data = [];
                    $nums_by_occp = 0;
                    foreach ($date_arr as $key => &$date_val) {
                        $data = DakouChild::with(['dakoku', 'support_company', 'supported_company', 'occupation', 'work_location', 'dakoku.user.user_data.break_times.organization'])
                            ->whereHas('dakoku', function ($query) use ($date_val, $org_val) {
                                $query->whereDate('target_date', $date_val['date']);
                                $query->whereHas('user.user_data.break_times.organization', function ($q) use ($org_val) {
                                    $q->where('id', $org_val['id']);
                                });
                            })
                            ->where(function ($q) use ($type, $comp_id, $location_id, $occupation_id) {
                                $q->where('dp_support_flg', $type);
                                if ($type == 1) {
                                    $q->where('dp_supported_company_id', $comp_id);
                                } else {
                                    $q->where('dp_support_company_id', $comp_id);
                                }
                                $q->where('dp_nums_of_people', '!=', null);
                                $q->where('dp_work_location_id', $location_id);
                                $q->where('dp_occupation_id', $occupation_id);
                            })
                            ->get();
                        if (!empty($data)) {
                            $nums_of_people = 0;
                            foreach ($data as $val) {
                                $nums_of_people += $val->dp_nums_of_people;
                            }
                            $row_data[date('j', strtotime($date_val['date']))] = $nums_of_people;
                            $nums_by_occp += $nums_of_people;
                            $date_val['sum_by_date'] += $nums_of_people;
                        }
                    }
                    $sheet_data[$org_val['id']]['comp_data'][$comp_id]['location'][$location_id]['occps'][$occupation_id] = [
                        'occp_id'     => $occupation_id,
                        'occp_name'   => $val->occupation?->occupation_name,
                        'row_data'    => $row_data,
                        'sum_by_occp' => $nums_by_occp
                    ];
                }
            }
        }
        if (count($sheet_data) == 0) {
            return response()->json(['success' => false, 'message' =>  'データはありません。']);
        }

        // reassign key as 0 ~, this action is necessary to handle excel.
        $sheet_data = array_values($sheet_data);
        foreach ($sheet_data as &$org) {
            $org['comp_data'] = array_values($org['comp_data']);
            foreach ($org['comp_data'] as &$company) {
                $company['location'] = array_values($company['location']);
                foreach ($company['location'] as &$location) {
                    $location['occps'] = array_values($location['occps']);
                }
            }
        }
        if ($type == 1) {
            // 応援に行った Supported Sheet Data
            $temp_file = base_path('template/supported_flg_export.xlsx');
        }
        if ($type == 2) {
            // 応援に来てもらった Support Company Sheet Data
            $temp_file = base_path('template/support_flg_export.xlsx');
        }
        $spreadsheet = IOFactory::load($temp_file);
        $sheet       = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A3', $title);

        $rows = 0;
        $skip_org_rows = 0;
        $skip_comp_rows = 0;
        $skip_loc_rows = 0;
        foreach ($sheet_data as $org_key => $org_val) {
            $org_rows = 0;
            foreach ($org_val['comp_data'] as $comp_key => $comp_val) {
                $loc_rows = 0;
                foreach ($comp_val['location'] as $loc_key => $loc_val) {
                    $occp_rows = 0;
                    foreach ($loc_val['occps'] as $occp_key => $occp_val) {
                        $sheet->setCellValue('D' . ($rows + 5), $occp_val['occp_name']);
                        foreach ($occp_val['row_data'] as $date_key => $nums) {
                            $sheet->setCellValueByColumnAndRow($date_key + 4, $rows + 5, $nums > 0 ? $nums : "");
                        }
                        $sheet->setCellValue('AJ' . $rows + 5, $occp_val['sum_by_occp'] > 0 ? $occp_val['sum_by_occp'] : "");
                        $rows++;
                        $org_rows++;
                        $occp_rows++;
                        $loc_rows++;
                    }
                    $sheet->mergeCells('C' . ($skip_loc_rows + 5) . ':C' . ($skip_loc_rows + $occp_rows + 4));
                    $sheet->setCellValue('C' . $skip_loc_rows + 5, $loc_val['loc_name']);
                    $skip_loc_rows += $occp_rows;
                }
                $sheet->mergeCells('B' . ($skip_comp_rows + 5) . ':B' . ($skip_comp_rows + $loc_rows + 4));
                $sheet->setCellValue('B' . ($skip_comp_rows + 5), $comp_val['comp_name']);
                $skip_comp_rows += $loc_rows;
            }
            $sheet->mergeCells('A' . ($skip_org_rows + 5) . ':A' . ($skip_org_rows + $org_rows + 4));
            $sheet->setCellValue('A' . ($skip_org_rows + 5), $org_val['org_name']);
            $skip_org_rows += $org_rows;
        }

        $supped_daily_sums = 0;
        foreach ($date_arr as $key => $val) {
            $formatted_date = sprintf("%d\n(%s)", date('j', strtotime($val['date'])), $val['week_day']);
            $sheet->setCellValue($this->row_date_set_cols[$key + 1] . 4, $formatted_date);

            if ($val['week_day'] == '日') {
                $sheet->getStyle($this->row_date_set_cols[$key + 1] . 4)->getFont()->setColor(new Color(Color::COLOR_RED));
                $sheet->getStyle($this->row_date_set_cols[$key + 1] . 4)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD5D5D5');
            } else if ($val['week_day'] == '土') {
                $sheet->getStyle($this->row_date_set_cols[$key + 1] . 4)->getFont()->setColor(new Color(Color::COLOR_BLUE));
                $sheet->getStyle($this->row_date_set_cols[$key + 1] . 4)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD5D5D5');
            }

            // 日別合計B~AG
            if (count($sheet_data) > 0) {
                $supped_daily_sums += $val['sum_by_date'];
                $sheet->setCellValue($this->row_date_set_cols[$key + 1] . $rows + 5, $val['sum_by_date'] > 0 ? $val['sum_by_date'] : "");
            }
        }
        $sheet->setCellValue('AJ' . $rows + 5, $supped_daily_sums);

        // // Merge A~C cell
        $sheet->mergeCells('A' . ($rows + 5) . ':D' . ($rows + 5));
        $sheet->setCellValue('A' . ($rows + 5), '計');
        $sheet->getStyle('A5:AJ' . ($rows + 5))->applyFromArray($this->sheet_style);

        $writer = new Xlsx($spreadsheet);
        $fname = sprintf('%s%d月_応援情報出力.xlsx', $jp_year, $month);
        $writer->save('storage/' . $fname);

        return response()->json(['success' => true, 'path' => $fname]);
    }

    // 現場別集計
    public function exportLocationMansData(Request $request)
    {
        $start_date    = $request->input('startDate');
        $close_date    = $request->input('closeDate');
        $location_name = $request->input('loc');
        $collect_data  = $request->input('data');
        $date_arr      = $request->input('dateRange');
        $temp_file     = base_path('template/location_collect.xlsx');
        $spreadssheet  = IOFactory::load($temp_file);
        $sheet         = $spreadssheet->getActiveSheet();

        $year          = explode('/', $start_date)[0];
        $jp_year = Helper::getJpDate($year, 'year');
        $date_arr = [];

        $s_date = Carbon::parse($start_date);
        $c_date = Carbon::parse($close_date);
        while ($s_date->isBefore(Carbon::parse($c_date)->addDay())) {
            $date_arr[] = [
                'date'        => $s_date->format('Y-m-d'),
                'sum_by_date' => 0
            ];
            $s_date = Carbon::parse($s_date)->addDay();
        }

        $header_title = sprintf("%s～%s", $start_date, $close_date);

        $sheet->setCellValue('A2', $location_name . '現場');
        $sheet->setCellValue('B2', $header_title);
        // Merge
        $sheet->mergeCellsByColumnAndRow(3, 2, count($date_arr) + 4, 2);

        $rows = 0;
        $skip_ocp_rows = 0;
        foreach ($collect_data as $org_key => $org_val) {
            $ocp_rows = 0;
            foreach ($org_val['occps'] as $ocp_key => $ocp_val) {
                $wc_rows = 0;
                $work_content_len = count($ocp_val['work_contents']);
                for ($i = 0; $i < $work_content_len + 1; $i++) {
                    if ($i < $work_content_len) {
                        $sheet->setCellValue('C' . $rows + 4, $ocp_val['work_contents'][$i]['woc_name']);
                        $row_data = array_values($ocp_val['work_contents'][$i]['row_data']);
                        $sheet->fromArray([$row_data], null, 'D' . $rows + 4);
                        $sheet->setCellValueByColumnAndRow(count($date_arr) + 4, $rows + 4, $ocp_val['work_contents'][$i]['sum_by_content']);
                    } else {
                        $sheet->setCellValue('C' . $rows + 4, "応援に来てもらった人数");
                    }
                    $rows++;
                    $wc_rows++;
                }
                // $sheet->getStyle('C' . ($rows + 3) . ':AI' . ($rows + 3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD5D5D5');
                $sheet->fromArray([$ocp_val['nums_of_people']], null, 'D' . $rows + 3);
                $sheet->setCellValueByColumnAndRow(count($ocp_val['nums_of_people']) + 4, $rows + 3, array_sum($ocp_val['nums_of_people']));
                $sheet->mergeCells('B' . ($skip_ocp_rows + $ocp_rows + 4) . ':B' . ($skip_ocp_rows + $ocp_rows + $wc_rows + 3));
                $sheet->setCellValue('B' . $skip_ocp_rows + $ocp_rows + 4, $ocp_val['occp_name']);
                $ocp_rows += count($ocp_val['work_contents']) + 1;
            }

            $sheet->mergeCells('A' . ($skip_ocp_rows + 4) . ':A' . ($skip_ocp_rows + $ocp_rows + 3));
            $sheet->setCellValue('A' . ($skip_ocp_rows + 4), $org_val['org_name']);
            $skip_ocp_rows += $ocp_rows;
        }

        foreach ($date_arr as $key => $val) {
            $weekday = Helper::getWeekday($val['date']);
            $formatted_date = sprintf("%d\n(%s)", date('j', strtotime($val['date'])), $weekday);
            $sheet->setCellValueByColumnAndRow($key + 4, 3, $formatted_date);
            if ($weekday == '日') {
                $sheet->getStyleByColumnAndRow($key + 4, 3)->getFont()->setColor(new Color(Color::COLOR_RED));
                $sheet->getStyleByColumnAndRow($key + 4, 3)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD5D5D5');
            } else if ($weekday == '土') {
                $sheet->getStyleByColumnAndRow($key + 4, 3)->getFont()->setColor(new Color(Color::COLOR_BLUE));
                $sheet->getStyleByColumnAndRow($key + 4, 3)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD5D5D5');
            }
            $sheet->getStyleByColumnAndRow($key + 4, 3)->getFont()->setSize(10);
            $sheet->getColumnDimensionByColumn($key + 4)->setWidth(4);
            $sheet->getStyleByColumnAndRow($key + 4, 3)->getAlignment()->setWrapText(true);
        }
        $sheet->setCellValueByColumnAndRow(count($date_arr) + 4, 3, '計');
        $sheet->getColumnDimensionByColumn(count($date_arr) + 4)->setWidth(6);
        $sheet->getStyleByColumnAndRow(1, 3, count($date_arr) + 4, $rows + 3)->applyFromArray($this->sheet_style);
        $writer = new Xlsx($spreadssheet);
        $fname = sprintf('%s～%s_現場別集計.xlsx', Carbon::parse($start_date)->format("Y-m-d"), Carbon::parse($close_date)->format("Y-m-d"));
        $writer->save('storage/' . $fname);

        return response()->json(['success' => true, 'path' => $fname]);
    }
}
