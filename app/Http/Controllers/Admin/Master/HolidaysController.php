<?php

namespace App\Http\Controllers\Admin\Master;

use App\Helper\ExcelExport;
use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\CsvFileRequest;
use App\Http\Requests\HolidayRequest;
use App\Imports\HolidayCsvImport;
use App\Imports\HolidayImport;
use App\Models\CompanySetting;
use App\Models\Holiday;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class HolidaysController extends Controller
{
    public function index(Request $request)
    {
        $key      = $request->input("key");
        $id       = $request->input("id");
        $sdate    = $request->input("sdate");
        $cdate    = $request->input("cdate");
        $paid     = $request->input("paid_holiday");
        $holidays = Holiday::when($key, function ($query) use ($key) {
            $query->where("holiday_date", "like", "%" . $key . "%");
        })->when($id, function ($q) use ($id) {
            $q->where("id", $id);
        })->when($sdate, function ($q) use ($sdate) {
            $q->whereDate("holiday_date", ">=", $sdate);
        })->when($cdate, function ($q) use ($cdate) {
            $q->whereDate("holiday_date", "<=", $cdate);
        })->where(function ($query) use ($paid) {
            if($paid == "true") {
                $query->where("paid_holiday", true);
            }
        })->orderBy('holiday_date', 'DESC')->paginate(100)->withQueryString();
        return Inertia::render('Admin/Master/Holidays/HolidaysIndex', compact('holidays'));
    }

    public function create()
    {
        return Inertia::render('Admin/Master/Holidays/CreateHoliday');
    }

    public function calendar()
    {
        return Inertia::render('Admin/Master/Holidays/HolidayCalendar');
    }

    public function exportCalendar(Request $request)
    {
        $list        = $request->input('list');
        $start_date  = $request->input('startDate');
        $end_date    = $request->input('endDate');
        $holidays    = Holiday::whereDate('holiday_date', '>=', $start_date)->whereDate('holiday_date', '<=', $end_date)->orderBy('holiday_date')->select('id', 'holiday_date', 'paid_holiday', 'holiday_flag' )->get()?->toArray();
        $temp_file   = base_path('template/calendar.xlsx');
        $spreadsheet = IOFactory::load($temp_file);
        $sheet       = $spreadsheet->getActiveSheet();

        $date_title = Carbon::parse($start_date)->format('Y年n月j日') . "～" . Carbon::parse($end_date)->format('Y年n月j日');
        $sheet->setCellValue('R3', $date_title);
        $company_title = CompanySetting::first()->value('company_name') ?? "株式会社";
        $header_title = sprintf("%d年度　年間休日カレンダー", date('Y', strtotime($start_date)));
        $sheet->setCellValue('D1', $company_title);
        $sheet->setCellValue('D2', $header_title);

        foreach ($list as $key => $month) {
            $month_label = date('n', strtotime($month[1][0]));
            $sheet->setCellValueByColumnAndRow(($key % 3) * 8 + 4, floor($key / 3) * 9 + 5, "$month_label 月");
            foreach ($month as $rkey => $row) {
                foreach ($row as $ckey => $val) {
                    // from D column, so column number is 4
                    // start 7 row
                    $sheet->setCellValueByColumnAndRow(($key % 3) * 8 + $ckey + 4, floor($key / 3) * 9 + $rkey + 7,  $val ? date('j', strtotime($val)) : "");

                    $holiday_check = array_filter($holidays, function ($item) use ($val) {
                        if ($val) {
                            return Carbon::parse($item['holiday_date'])->isSameDay(Carbon::parse($val)) && $item['paid_holiday'] == 0;
                        } else {
                            return null;
                        }
                    });
                    $paid_holiday_check = array_filter($holidays, function ($item) use ($val) {
                        if ($val) {
                            return Carbon::parse($item['holiday_date'])->isSameDay(Carbon::parse($val)) && $item['paid_holiday'] == 1;
                        } else {
                            return null;
                        }
                    });
                    if ($holiday_check) {
                        $style = $sheet->getStyleByColumnAndRow(($key % 3) * 8 + $ckey + 4, floor($key / 3) * 9 + $rkey + 7,);
                        $style->getFont()->setColor(new Color(Color::COLOR_RED));
                    }
                    if ($paid_holiday_check) {
                        $style = $sheet->getStyleByColumnAndRow(($key % 3) * 8 + $ckey + 4, floor($key / 3) * 9 + $rkey + 7,)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('CCFFFF00');
                    }
                }
            }
        }

        // 各月の所定労働時間					
        $month_arr = [];
        for ($i = 0; $i < 12; $i++) {
            $month_arr[$i] = Carbon::parse($start_date)->addMonths($i)->format('Y-m');
        }

        $sum_max_date      = 0;
        $sum_holidays      = 0;
        $sum_working_days  = 0;
        $sum_working_times = 0;
        $sum_paid_holidays = 0;
        foreach ($month_arr as $key => $month) {
            $max_date = date('t', strtotime($month));
            $holidays_count = count(array_filter($holidays, function ($item) use ($month) {
                return Carbon::parse($item['holiday_date'])->isSameMonth(Carbon::parse($month)) && $item['paid_holiday'] == 0;
            }));
            $sum_paid_holidays += count(array_filter($holidays, function ($item) use ($month) {
                return Carbon::parse($item['holiday_date'])->isSameMonth(Carbon::parse($month)) && $item['paid_holiday'] == 1;
            }));
            $working_days  = $max_date - $holidays_count;
            $working_times = $working_days * 7;

            $sum_max_date      += $max_date;
            $sum_holidays      += $holidays_count;
            $sum_working_days  += $working_days;
            $sum_working_times += $working_times;

            // writing cell value
            $sheet->setCellValue('E' . $key + 43, $max_date);
            $sheet->setCellValue('G' . $key + 43, $holidays_count);
            $sheet->setCellValue('I' . $key + 43, $working_days);
            $sheet->setCellValue('K' . $key + 43, $working_times);
        }

        // 合計
        $sheet->setCellValue('E55', $sum_max_date);
        $sheet->setCellValue('G55', $sum_holidays);
        $sheet->setCellValue('I55', $sum_working_days);
        $sheet->setCellValue('K55', $sum_working_times);

        $sheet->setCellValue('V42', sprintf('年間休日　%d日', $sum_holidays));
        $sheet->setCellValue('X43', sprintf('%d日', $sum_working_days));
        $sheet->setCellValue('X44', sprintf('%s時間', $sum_working_times));
        $sheet->setCellValue('X45', sprintf('%s日', $sum_paid_holidays));

        $startYearMonth = Carbon::parse($start_date)->format('Y年n月');
        $endYearMonth   = Carbon::parse($end_date)->format('Y年n月');
        $format         = '%s～%s　年間休日カレンダー.xlsx';

        $sheet->setTitle(sprintf("%s～%s　年間休日カレンダー", $startYearMonth, $endYearMonth));

        $writer = new Xlsx($spreadsheet);
        $fname          = sprintf($format, $startYearMonth, $endYearMonth);
        $writer->save('storage/' . $fname);

        return response()->json(['path' => $fname]);
    }
    public function getHolidays(Request $request)
    {
        $current_year = $request->input('year') ?? date('Y');
        $start_date   = Carbon::create($current_year, 4, 1)->format('Y-m-d');
        $end_date     = Carbon::create($current_year, 3, 31)->add(1, 'year')->format('Y-m-d');
        $holidays     = Holiday::whereDate('holiday_date', '>=', $start_date)->whereDate('holiday_date', '<=', $end_date)->get();
        return response()->json($holidays);
    }

    public function store(Request $request)
    {
        if ($request) {
            $holiday = $request->holidayDate;
            if (is_array($holiday)) {
                // カレンダーから登録するとき
                $request->validate(
                    ['holidayDate' => 'required|array|min:1'],
                    ['holidayDate.required' => '休日を選択してください。'],
                );
                // Holiday::truncate();die;
                $year = $request->input('year');
                $start_date = Carbon::create($year, 4, 1)->format('Y-m-d');
                $end_date = Carbon::parse($start_date)->addYears(1)->subDays(1)->format('Y-m-d');
                $old_holiday_arr = Holiday::whereDate('holiday_date', '>=', $start_date)->whereDate('holiday_date', '<=', $end_date)->pluck('holiday_date');
                $holiday_date = collect($holiday)->map(function ($item) {
                    return $item['date'];
                })?->toArray();
                $remove_item_arr = array_diff($old_holiday_arr->toArray(), $holiday_date);
                Holiday::whereIn('holiday_date', $remove_item_arr)->delete();

                DB::transaction(function () use ($holiday) {
                    foreach ($holiday as $item) {
                        Holiday::updateOrCreate(
                            [
                                'holiday_date'  => $item['date'],
                            ],
                            [
                                'holiday_flag'  => 1,
                                'paid_holiday'  => $item['paid'],
                            ]
                        );
                    }
                });
            } else {
                // フォームによる登録
                $request->validate(
                    ['holidayDate' => 'required|date_format:Y/m/d'],
                    ['holidayDate.required' => '休日を選択してください。'],
                );
                $holiday = Holiday::create([
                    'holiday_date'  => $request->holidayDate,
                    'holiday_flag'  => 1,
                    'paid_holiday'  => $request->input('paidHolidayVisible'),
                ]);
            }

            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.holiday.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.holiday.show', ['id' => $holiday->id]);
            } else {
                return redirect()->route('admin.master.holiday.create');
            }
        }
    }
    public function show($id)
    {
        $holidays = Holiday::find($id);
        return Inertia::render('Admin/Master/Holidays/HolidayDetail', compact('holidays'));
    }
    public function edit($id)
    {
        $holidays = Holiday::find($id);

        return Inertia::render('Admin/Master/Holidays/HolidayEdit', compact('holidays'));
    }
    public function update(HolidayRequest $request)
    {
        if ($request) {
            Holiday::where('id', $request->id)->update([
                'holiday_date' => $request->holidayDate,
                'paid_holiday' => $request->paidHolidayVisible
            ]);

            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.holiday.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.holiday.show', ['id' => $request->id]);
            } else {
                return redirect()->route('admin.master.holiday.create');
            }
        }
    }
    public function destroy(Request $request)
    {
        if ($request) {
            Holiday::where('id', $request->id)->delete();
            return redirect()->route('admin.master.holiday.index');
        }
    }
    public function destroyYear(Request $request)
    {
        $year       = $request->input('year');
        $start_date = Carbon::create($year, 4, 1);
        $end_date   = Carbon::create($year+1, 3, 31);
        Holiday::whereDate('holiday_date', '>=', $start_date)->whereDate('holiday_date', "<=", $end_date)->delete();
        return redirect()->route('admin.master.holiday.index');
    }
    public function export(Request $request)
    {
        try {
            $type             = $request->input('type');
            $file_name_format = "休日管理_%d.%s";
            $file_name        = sprintf($file_name_format, date('YmdHis'), $type);
            $path             = 'master/' . $file_name;
            $holiday_data        = Holiday::query();

            $csv_data   = $holiday_data->get();
            $excel_data = $holiday_data->get()->collect()->map(function ($item) {
                return [
                    'id'           => $item->id,
                    'holiday_date' => $item->holiday_date,
                    'created_at'   => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
                    'updated_at'   => Carbon::parse($item->updated_at)->format('Y-m-d H:i:s'),
                ];
            });
            $heading = ["ID", "休日", "作成日時", "更新日時"];
            if ($type == 'csv') {
                $status = ExcelExport::exportCsv($csv_data, $heading, $path);
            } else if ($type == 'xlsx') {
                $status = ExcelExport::exportExcel($excel_data, $heading, $path);
            }
            if ($status) {
                return response()->json(['path' => $path]);
            }
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }
    public function import(CsvFileRequest $request)
    {
        try {
            $csv = $request->file('file');
            Excel::import(new HolidayImport, $csv);
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
