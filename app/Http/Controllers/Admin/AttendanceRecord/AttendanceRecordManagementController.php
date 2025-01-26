<?php

namespace App\Http\Controllers\Admin\AttendanceRecord;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\AttendStatus;
use App\Models\BreakTime;
use App\Models\DakouChild;
use App\Models\DakouData;
use App\Models\Holiday;
use App\Models\Occupation;
use App\Models\Organization;
use App\Models\SupportCompany;
use App\Models\SupportedCompany;
use App\Models\User;
use App\Models\UserData;
use App\Models\WorkContent;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use function PHPUnit\Framework\isNull;

class AttendanceRecordManagementController extends Controller
{
    public function specialIndex(Request $request)
    {
        $users = User::users();
        $breakTimes = BreakTime::all();
        return Inertia::render('Admin/Record/Special/Index', compact('users', 'breakTimes'));
    }

    public function monthIndex(Request $request)
    {
        $user_id           = $request->userId;
        $search_date       = $request->date ?? date('Y/m/d');
        $users             = User::users();
        return Inertia::render('Admin/Record/Month/Index', compact('user_id', 'users', 'search_date'));
    }

    // axios method
    public function search(Request $request)
    {
        $filter         = $request->filter;
        $year           = Carbon::parse($filter['date'])->format('Y');
        $month          = Carbon::parse($filter['date'])->format('m');
        $attended_users = DakouData::whereYear('target_date',  $year)->whereMonth('target_date', $month)->groupBy('dp_user')->orderBy('dp_user', 'asc')->pluck('dp_user');
        $dakou_data     = $this->get_dakoku_data($request);
        // 集計データの計算(労働日数,労働時間)
        $user = $filter['user'] ?? null;
        if ($user) {
            if ($filter['type'] == 'monthly') {
                $dakou_data = $this->get_monthly_data($filter['date'], $filter['user']);
                $collect_data = $this->get_monthly_collect_data($request, $dakou_data, $user);
            }

            $user_data = UserData::where('user_id', $user)->first();
            $org = $user_data->break_times->organization;
            $parent_org = $org->parentOrg;
            return response()->json([
                'dakoku'                    => $dakou_data,
                'attended_users'            => count($attended_users),
                'collect_days'              => $collect_data['collect_days'] ?? null,
                'collect_times'             => $collect_data['collect_times'] ?? null,
                'collect_other_flag_count'  => $collect_data['collect_other_flag_count'] ?? null,
                'work_content'              => $collect_data['work_content'] ?? null,
                'occupations_collect'       => $collect_data['occupations_collect'] ?? null,
                'support_company_collect'   => $collect_data['support_company_collect'] ?? null,
                'supported_company_collect' => $collect_data['supported_company_collect'] ?? null,
                'driver_ride_collect'       => $collect_data['driver_ride_collect'] ?? null,
                'org'                       => $org,
                'parent_org'                => $parent_org,
            ]);
        } else {
            return response()->json([
                'dakoku'                    => $dakou_data,
                'attended_users'            => count($attended_users),
                'collect_days'              => null,
                'collect_times'             => null,
                'collect_other_flag_count'  => null,
                'work_content'              => null,
                'occupations_collect'       => null,
                'support_company_collect'   => null,
                'supported_company_collect' => null,
                'driver_ride_collect'       => null,
                'org'                       => null,
                'parent_org'                => null,
            ]);
        }
    }

    public function get_dakoku_data($request)
    {
        $filter     = $request->filter;
        $year       = Carbon::parse($filter['date'])->format('Y');
        $month      = Carbon::parse($filter['date'])->format('m');
        return DakouData::with([
            'user.user_data.break_times',
            'attend_type',
            'attend_status',
            'dakoku_children',
            'dakoku_children.support_company',
            'dakoku_children.occupation',
            'dakoku_children.work_content',
            'dakoku_children.work_location',
            'dakoku_children.worker_master',
            'dakoku_children.timezone',
        ])->leftJoin('holidays', function ($join) {
            $join->on('dakou_data.target_date', '=', 'holidays.holiday_date');
        })->where(function ($q) use ($filter, $year, $month) {
            if ($filter['type'] == 'daily') {
                if (isset($filter['date'])) {
                    $q->whereDate('target_date', $filter['date']);
                }
                if (isset($filter['user'])) {
                    $q->where('dp_user', $filter['user']);
                }
                if (isset($filter['break'])) {
                    $q->whereRelation('user.user_data.break_times', 'id', $filter['break']['id']);
                }
            } else {
                $q->where('dp_user', $filter['user'] ?? null);
                $q->whereYear('target_date', $year);
                $q->whereMonth('target_date', $month);
            }
        })->select('dakou_data.*', 'holidays.holiday_date', 'holidays.paid_holiday')
            ->orderBy('target_date', 'DESC')
            ->get();
    }

    public function get_monthly_data($date, $user_id)
    {
        $max_day    = date('t', strtotime($date));
        $year       = date('Y', strtotime($date));
        $month      = date('m', strtotime($date));
        $start_date = Carbon::create($year, $month, 1);
        $end_date   = Carbon::create($year, $month, $max_day);
        $daily_data = [];

        while ($start_date <= $end_date) {
            $dakoku               = DakouData::with(['attend_status', 'attend_type', 'user.user_data.break_times'])->whereDate('target_date', $start_date)->where('dp_user', $user_id)->first();
            $gen_holiday          = Holiday::whereDate('holiday_date', $start_date)->where('paid_holiday', 0)->exists();
            $paid_holiday         = Holiday::whereDate('holiday_date', $start_date)->where('paid_holiday', 1)->exists();
            if ($gen_holiday) {
                $holiday = '休日';
            } else if ($paid_holiday) {
                $holiday = '有給';
            } else {
                $holiday = "";
            }
            if ($dakoku) {
                $break  = $dakoku->user?->user_data?->break_times;
                $bs     = $break?->break_start_time;
                $be     = $break?->break_end_time;
                $bs_1   = $break?->break_start_time1;
                $be_1   = $break?->break_end_time1;
                $bs_2   = $break?->break_start_time2;
                $be_2   = $break?->break_end_time2;
                $bs_3   = $break?->break_start_time3;
                $be_3   = $break?->break_end_time3;
                $status = true;                                     //attend status
                if ($break) {
                    $shift_start = Carbon::parse($bs);
                    $shift_close = Carbon::parse($be);
                    $shift = sprintf("%s～%s", $shift_start->format("H:i"), $shift_close->format("H:i"));
                    $shift_times_seconds = $shift_start->diffInSeconds($shift_close);
                    $shift_times = Carbon::parse($shift_times_seconds)->format("H:i");
                } else {
                    $shift_times_seconds = 0;
                }

                $syukin_time       = $dakoku->dp_syukkin_time;
                $taikin_time       = $dakoku->dp_taikin_time;
                $rest_all          = 0;
                $work_time_seconds = 0;
                if ($syukin_time && $taikin_time) {

                    // *** 出勤時間、退勤時間、休憩時間を考慮した実労働時間、休憩時間の計算
                    if ($bs_1 && $be_1) {
                        // ex: syukin 09:00:00, taikin: 15:00:00. break1: 10:00~10:30
                        // 出勤時間がセットされた休憩時間1より前の場合
                        if (strtotime($syukin_time) <= strtotime($bs_1)) {
                            if (strtotime($taikin_time) >= strtotime($be_1)) {
                                $rest_all += Carbon::parse($be_1)->diffInSeconds(Carbon::parse($bs_1));
                            } else {
                                if (strtotime($taikin_time) >= strtotime($bs_1)) {
                                    $rest_all += Carbon::parse($taikin_time)->diffInSeconds(Carbon::parse($bs_1));
                                }
                            }
                        } else if (strtotime($syukin_time) > strtotime($bs_1) && strtotime($syukin_time) < strtotime($be_1)) {
                            if (strtotime($taikin_time) >= strtotime($be_1)) {
                                $rest_all += Carbon::parse($be_1)->diffInSeconds(Carbon::parse($syukin_time));
                            } else {
                                $rest_all += Carbon::parse($taikin_time)->diffInSeconds(Carbon::parse($syukin_time));
                            }
                        }
                    }
                    if ($bs_2 && $be_2) {
                        // ex: syukin 09:00:00, taikin: 15:00:00. break1: 10:00~10:30
                        // 出勤時間がセットされた休憩時間1より前の場合
                        if (strtotime($syukin_time) <= strtotime($bs_2)) {
                            if (strtotime($taikin_time) >= strtotime($be_2)) {
                                $rest_all += Carbon::parse($be_2)->diffInSeconds(Carbon::parse($bs_2));
                            } else {
                                if (strtotime($taikin_time) >= strtotime($bs_2)) {
                                    $rest_all += Carbon::parse($taikin_time)->diffInSeconds(Carbon::parse($bs_2));
                                }
                            }
                        } else if (strtotime($syukin_time) > strtotime($bs_2) && strtotime($syukin_time) < strtotime($be_2)) {
                            if (strtotime($taikin_time) >= strtotime($be_2)) {
                                $rest_all += Carbon::parse($be_2)->diffInSeconds(Carbon::parse($syukin_time));
                            } else {
                                $rest_all += Carbon::parse($taikin_time)->diffInSeconds(Carbon::parse($syukin_time));
                            }
                        }
                    }
                    if ($bs_3 && $be_3) {
                        // ex: syukin 09:00:00, taikin: 15:00:00. break1: 10:00~10:30
                        // 出勤時間がセットされた休憩時間1より前の場合
                        if (strtotime($syukin_time) <= strtotime($bs_3)) {
                            if (strtotime($taikin_time) >= strtotime($be_3)) {
                                $rest_all += Carbon::parse($be_3)->diffInSeconds(Carbon::parse($bs_3));
                            } else {
                                if (strtotime($taikin_time) >= strtotime($bs_3)) {
                                    $rest_all += Carbon::parse($taikin_time)->diffInSeconds(Carbon::parse($bs_3));
                                }
                            }
                        } else if (strtotime($syukin_time) > strtotime($bs_3) && strtotime($syukin_time) < strtotime($be_3)) {
                            if (strtotime($taikin_time) >= strtotime($be_3)) {
                                $rest_all += Carbon::parse($be_3)->diffInSeconds(Carbon::parse($syukin_time));
                            } else {
                                $rest_all += Carbon::parse($taikin_time)->diffInSeconds(Carbon::parse($syukin_time));
                            }
                        }
                    }

                    $rest_time = Carbon::parse($rest_all)->format("H:i");

                    /**
                     * 勤務形態で、定義された時間、
                     * ※添付だと、9:00~15:00
                     * を超えた時間は実労働に含まなくて良いです。
                     * 9じより前の打刻も同様です。
                     */
                    if ($bs && $be) {
                        if (strtotime($bs) <= strtotime($syukin_time)) {
                            if (strtotime($be) >= strtotime($taikin_time)) {
                                $work_time_seconds = Carbon::parse($syukin_time)->diffInSeconds(Carbon::parse($taikin_time));
                            } else {
                                $work_time_seconds = Carbon::parse($syukin_time)->diffInSeconds(Carbon::parse($be));
                            }
                        } else {
                            if (strtotime($be) >= strtotime($taikin_time)) {
                                $work_time_seconds = Carbon::parse($bs)->diffInSeconds(Carbon::parse($taikin_time));
                            } else {
                                $work_time_seconds = Carbon::parse($bs)->diffInSeconds(Carbon::parse($be));
                            }
                        }
                    }
                    $work_time = Carbon::parse($work_time_seconds - $rest_all)->format('H:i');

                    if ($work_time_seconds < $shift_times_seconds) {
                        $is_before = "lt";
                    } else {
                        $is_before = "gt";
                    }
                } else {
                    $work_time = "";
                }
                $other_flg         = $dakoku->attend_status?->attend_name;
            } else {
                $syukin_time       = "";
                $taikin_time       = "";
                $work_time         = "";
                $rest_time         = "";
                $rest_all          = 0;
                $work_time_seconds = 0;
                $other_flg         = "";
                $is_before         = "";
                $status            = false;
            }

            $key                                   = Carbon::parse($start_date)->format('j');
            $daily_data[$key]['id']                = $dakoku?->id;
            $daily_data[$key]['date']              = $start_date->format('Y-m-d');
            $daily_data[$key]['day']               = Helper::getWeekday($start_date);
            $daily_data[$key]['holiday']           = $holiday;
            $daily_data[$key]['type']              = $dakoku?->attend_type?->attend_type_name ?? "";
            $daily_data[$key]['dtype']             = $dakoku?->dp_type ?? "";
            $daily_data[$key]['shift']             = $shift ?? "";
            $daily_data[$key]['syukin']            = $syukin_time ? Carbon::parse($syukin_time)->format("H:i") : "";
            $daily_data[$key]['taikin']            = $taikin_time ? Carbon::parse($taikin_time)->format("H:i") : "";
            $daily_data[$key]['shift_times']       = $shift_times ?? "";
            $daily_data[$key]['work_time']         = $work_time ?? "";
            $daily_data[$key]['work_time_seconds'] = $work_time_seconds;
            $daily_data[$key]['rest_seconds']      = $rest_all;
            $daily_data[$key]['rest_time']         = $rest_time ?? "";
            $daily_data[$key]['other_flg']         = $other_flg ?? "";
            $daily_data[$key]['is_before']         = $is_before ?? "";
            $daily_data[$key]['status']            = $status;
            $start_date                            = $start_date->addDays(1);
        }
        $daily_data = array_values($daily_data);
        return $daily_data;
    }

    public function get_monthly_collect_data($request, $daily_data, $user_id)
    {
        $filter     = $request->filter;
        $year       = Carbon::parse($filter['date'])->format('Y');
        $month      = Carbon::parse($filter['date'])->format('m');

        $days          = Carbon::parse($filter['date'])->daysInMonth;
        $holidays      = Holiday::whereYear('holiday_date', $year)->whereMonth('holiday_date', $month)->where('paid_holiday', false)->pluck('holiday_date');
        $paid_holidays = Holiday::whereYear('holiday_date', $year)->whereMonth('holiday_date', $month)->where('paid_holiday', true)->pluck('holiday_date');
        $rodo_days     = $days - count($holidays);

        $syukkin_data = DakouData::whereYear('target_date', $year)
            ->whereMonth('target_date', $month)
            ->where('dp_user', $user_id)
            ->whereNotIn('target_date', $holidays)
            ->get();
        $syukkin_days = count($syukkin_data);

        // 欠勤日数：休日テーブルの休日を除いた打刻のない日数
        $kekkin_days = $rodo_days - $syukkin_days;

        // 実働日数：出勤打刻がある日数
        $jitsudo_data = DakouData::with('user.user_data.break_times')->whereYear('target_date', $year)
            ->whereMonth('target_date', $month)
            ->where('dp_user', $user_id)
            ->get();
        $jitsudo_days = count($jitsudo_data);

        // 休日出勤日数：休日テーブルで定義した休日に出勤した回数
        $holiday_syukkin_days = DakouData::whereYear('target_date', $year)
            ->whereMonth('target_date', $month)
            ->where('dp_user', $user_id)
            ->whereIn('target_date', $holidays)
            ->count();

        $paid_holiday_syukkin_data = DakouData::whereYear('target_date', $year)
            ->whereMonth('target_date', $month)
            ->where('dp_user', $user_id)
            ->whereIn('target_date', $paid_holidays)
            ->count();

        $sunday_syukkin_data = DakouData::whereYear('target_date', $year)
            ->whereMonth('target_date', $month)
            ->where('dp_user', $user_id)
            ->whereRaw('WEEKDAY(target_date) = 6')
            ->count();
        // 遅刻日数：勤務形態管理で定義した出勤時間より遅く出勤した回数
        $user_syukkin_time = User::find($user_id)?->user_data?->break_times?->break_start_time;
        if ($user_syukkin_time) {
            $chikoku_data = DakouData::whereYear('target_date', $year)
                ->whereMonth('target_date', $month)
                ->where('dp_syukkin_time', '>', $user_syukkin_time)
                ->where('dp_user', $user_id)
                ->get();
            $chikoku_days = count($chikoku_data);
        }

        // 早退日数：打刻で「残業、遅刻、早退、有給、研修など」で「早退」を選択した日数
        $user_taikin_time = User::find($user_id)?->user_data?->break_times?->break_end_time;
        if ($user_taikin_time) {
            $sotai_data = DakouData::whereYear('target_date', $year)
                ->whereMonth('target_date', $month)
                ->where('dp_user', $user_id)
                ->where('dp_taikin_time', '<', $user_taikin_time)->get();
            $sotai_days = count($sotai_data);
        }

        $collect_days_data = [
            'rodo'                 => $rodo_days,
            'syukkin'              => $syukkin_days,
            'kekkin'               => $kekkin_days,
            'sotai'                => $sotai_days ?? 0,
            'jitsudo'              => $jitsudo_days,
            'holiday_syukkin'      => $holiday_syukkin_days,
            'paid_holiday_syukkin' => $paid_holiday_syukkin_data,
            'sunday_syukkin_data'  => $sunday_syukkin_data,
            'chikoku'              => $chikoku_days ?? 0,
        ];

        // 2.労働時間
        $user_breaks = User::find($user_id)?->user_data?->break_times;
        if ($user_breaks) {
            $start = $user_breaks?->break_start_time;
            $end = $user_breaks?->break_end_time;

            $restDiffSeconds = 0;
            $rest_start_1 = $user_breaks?->break_start_time1;
            $rest_start_2 = $user_breaks?->break_start_time2;
            $rest_start_3 = $user_breaks?->break_start_time3;
            $rest_end_1 = $user_breaks?->break_end_time1;
            $rest_end_2 = $user_breaks?->break_end_time2;
            $rest_end_3 = $user_breaks?->break_end_time3;

            if ($rest_start_1 && $rest_end_1) {
                $restDiffSeconds += Carbon::parse($rest_end_1)->diffInSeconds(Carbon::parse($rest_start_1));
            }
            if ($rest_start_2 && $rest_end_2) {
                $restDiffSeconds += Carbon::parse($rest_end_2)->diffInSeconds(Carbon::parse($rest_start_2));
            }
            if ($rest_start_3 && $rest_end_3) {
                $restDiffSeconds += Carbon::parse($rest_end_3)->diffInSeconds(Carbon::parse($rest_start_3));
            }
            $diffSeconds = Carbon::parse($end)->diffInSeconds(Carbon::parse($start));

            $real_jitsuo_times     = 0;  // 実労働時間
            $jitsuo_times          = 0;  // 平日労働時間
            $holiday_working_times = 0;  // 休日労働時間
            $real_over_times       = 0;  // 実残業時間
            $over_times            = 0;  // 平日残業時間
            $holiday_over_times    = 0;  // 休日残業時間
            foreach ($daily_data as $item) {
                $real_jitsuo_times += $item['work_time_seconds'] - $item['rest_seconds'];
                if ($item['holiday'] != '休日') {
                    $jitsuo_times += $item['work_time_seconds'] - $item['rest_seconds'];
                } else {
                    $holiday_working_times += $item['work_time_seconds'] - $item['rest_seconds'];
                }

                if ($item['other_flg'] && mb_strpos($item['other_flg'], 'H 残業') != false) {
                    $time = str_replace('H 残業', '', $item['other_flg']);
                    $real_over_times += $time * 3600;
                }
                if ($item['other_flg'] && mb_strpos($item['other_flg'], 'H 残業') != false && $item['holiday'] != '休日') {
                    $time        = str_replace('H 残業', '', $item['other_flg']);
                    $over_times += $time * 3600;
                }
                if ($item['other_flg'] && mb_strpos($item['other_flg'], 'H 残業') != false && $item['holiday'] == '休日') {
                    $time                = str_replace('H 残業', '', $item['other_flg']);
                    $holiday_over_times += $time * 3600;
                }
            }
            // 実労働時間：勤務形態を無視し、労働した時間
            $real_jitsuo_times = Helper::secondsToTime($real_jitsuo_times);
            // 平日労働時間：所定労働日数に勤務形態で定義された、労働時間を掛けたもの
            $jitsuo_times = Helper::secondsToTime($jitsuo_times);
            // 休日労働時間：休日テーブルで定義した休日に労働した時間
            $holiday_working_times = Helper::secondsToTime($holiday_working_times);
            // 実残業時間：平日と休日の全ての残業時間を加算
            $real_over_times = Helper::secondsToTime($real_over_times);
            // 平日残業時間：勤務形態の勤務終了時刻を過ぎた時間
            $over_times = Helper::secondsToTime($over_times);
            // 休日残業時間：休日に勤務形態で定義された労働時間を過ぎて労働した時間
            $holiday_over_times = Helper::secondsToTime($holiday_over_times);
            $real_midnight_times = 0;
            $midnight_data = DakouData::whereYear('target_date', $year)
                ->whereMonth('target_date', $month)
                ->where('dp_user', $user_id)
                ->where(function ($q) {
                    $q->orWhere('dp_taikin_time', '<=', '05:00:00');
                    $q->orWhere('dp_taikin_time', '>=', '22:00:00');
                })
                ->get();
            foreach ($midnight_data as $val) {
                if (Carbon::parse($val->dp_syukkin_time) < Carbon::parse('22:00:00') && Carbon::parse($val->dp_syukkin_time) >  Carbon::parse('05:00:00')) {
                    if (Carbon::parse($val->dp_taikin_time) <= Carbon::parse('23:59:59')) {
                        // 22時前に出勤し、当日24時前に退勤した場合
                        $real_midnight_times += Carbon::parse('22:00:00')->diffInSeconds(Carbon::parse($val->dp_taikin_time));
                    } else {
                        // 22時前に出勤し、当日24時以降に退勤した場合
                        $real_midnight_times += Carbon::parse('1970-01-01 22:00:00')->diffInSeconds(Carbon::parse('1970-01-02 ' . $val->dp_taikin_time));
                    }
                } else {
                    if (Carbon::parse($val->dp_taikin_time) < Carbon::parse('00:00:00')) {
                        // 22時以降に出勤,当日24時前に退勤した場合
                        $real_midnight_times += Carbon::parse($val->dp_taikin_time)->diffInSeconds(Carbon::parse($val->dp_syukkin_time));
                    } else {
                        // 22時以降に出勤,当日24時以降に退勤した場合
                        // 22～24時の間に出勤
                        if (Carbon::parse($val->dp_syukkin_time) < Carbon::parse('24:00:00')) {
                            $real_midnight_times += Carbon::parse('1970-01-02 ' . $val->dp_taikin_time)->diffInSeconds(Carbon::parse('1970-01-01 ' . $val->dp_syukkin_time));
                        } else {
                            // 24時以降に出勤
                            $real_midnight_times += Carbon::parse($val->dp_taikin_time)->diffInSeconds(Carbon::parse($val->dp_syukkin_time));
                        }
                    }
                }
            }
            // 平日深夜時間：実深夜時間と同じ
            $real_midnight_times = Helper::secondsToTime($real_midnight_times);

            // 休日深夜時間：上記で更に22:00~5:00 までに労働した時間
            $holiday_midnight_times = 0;
            $holiday_midnight_data = DakouData::whereYear('target_date', $year)
                ->whereMonth('target_date', $month)
                ->where('dp_user', $user_id)
                ->whereIn('target_date', $holidays)
                ->where(function ($q) {
                    $q->orWhere('dp_taikin_time', '>=', '22:00:00');
                    $q->orWhere('dp_taikin_time', '<=', '05:00:00');
                })
                ->get();

            foreach ($holiday_midnight_data as $val) {
                if (Carbon::parse($val->dp_syukkin_time) < Carbon::parse('22:00:00') && Carbon::parse($val->dp_syukkin_time) >  Carbon::parse('05:00:00')) {
                    if (Carbon::parse($val->dp_taikin_time) <= Carbon::parse('23:59:59')) {
                        // 22時前に出勤し、当日24時前に退勤した場合
                        $holiday_midnight_times += Carbon::parse('22:00:00')->diffInSeconds(Carbon::parse($val->dp_taikin_time));
                    } else {
                        // 22時前に出勤し、当日24時以降に退勤した場合
                        $holiday_midnight_times += Carbon::parse('1970-01-01 22:00:00')->diffInSeconds(Carbon::parse('1970-01-02 ' . $val->dp_taikin_time));
                    }
                } else {
                    if (Carbon::parse($val->dp_taikin_time) < Carbon::parse('00:00:00')) {
                        // 22時以降に出勤,当日24時前に退勤した場合
                        $holiday_midnight_times += Carbon::parse($val->dp_taikin_time)->diffInSeconds(Carbon::parse($val->dp_syukkin_time));
                    } else {
                        // 22時以降に出勤,当日24時以降に退勤した場合
                        // 22～24時の間に出勤
                        if (Carbon::parse($val->dp_syukkin_time) < Carbon::parse('24:00:00')) {
                            $holiday_midnight_times += Carbon::parse('1970-01-02 ' . $val->dp_taikin_time)->diffInSeconds(Carbon::parse('1970-01-01 ' . $val->dp_syukkin_time));
                        } else {
                            // 24時以降に出勤
                            $holiday_midnight_times += Carbon::parse($val->dp_taikin_time)->diffInSeconds(Carbon::parse($val->dp_syukkin_time));
                        }
                    }
                }
            }
            $holiday_midnight_times = Helper::secondsToTime($holiday_midnight_times);

            $collect_times_data = [
                'working_times'          => $jitsuo_times,
                'real_working_times'     => $real_jitsuo_times,
                'midight_times'          => $real_midnight_times,
                'holiday_working_times'  => $holiday_working_times,
                'over_times'             => $over_times,
                'real_over_times'        => $real_over_times,
                'holiday_over_times'     => $holiday_over_times,
                'holiday_midnight_times' => $holiday_midnight_times,
            ];
        } else {
            $collect_times_data = [
                'working_times'          => null,
                'real_working_times'     => null,
                'midight_times'          => null,
                'holiday_working_times'  => null,
                'over_times'             => null,
                'real_over_times'        => null,
                'holiday_over_times'     => null,
                'holiday_midnight_times' => null,
            ];
        }

        // 3.残業、遅刻、早退、有給、研修など
        $attendOthers = AttendStatus::all();
        $attendCountArr = [];
        foreach ($attendOthers as $val) {
            $other_flag_count = DakouData::whereYear('target_date', $year)
                ->whereMonth('target_date', $month)
                ->where('dp_user', $user_id)
                ->where('dp_other_flg', $val->id)
                ->count();

            if ($other_flag_count > 0) {
                $attendCountArr[$val->id]['name'] = $val->attend_name;
                $attendCountArr[$val->id]['count'] = $other_flag_count;
            }
        }
        $attendCountArr = array_values($attendCountArr);

        $dakou_child_data = DakouChild::with([
            'dakoku' => function ($query) use ($year, $month, $user_id) {
                $query->where('dp_user', $user_id);
                $query->whereYear('target_date', $year);
                $query->whereMonth('target_date', $month);
            },
            'work_content',
            'occupation',
            'support_company',
            'supported_company'
        ])
            ->whereHas('dakoku', function ($query) use ($year, $month, $filter, $user_id) {
                $query->where('dp_user', $user_id);
                $query->whereYear('target_date', $year);
                $query->whereMonth('target_date', $month);
            })
            ->get();
        $work_content              = [];
        $occupations_collect       = [];
        $support_company_collect   = [];
        $supported_company_collect = [];
        foreach ($dakou_child_data as $key => $val) {
            // counting working content
            if ($val['$work_content']) {
                $work_content[$val['work_content']['id']]['name'] = $val['work_content']['work_content_name'];
                if (isset($work_content[$val['work_content']['id']]['count'])) {
                    $work_content[$val['work_content']['id']]['count'] += 1;
                } else {
                    $work_content[$val['work_content']['id']]['count'] = 1;
                }
            }
            // couting occupations
            if ($val['occupation']) {
                $occupations_collect[$val['occupation']['id']]['name'] = $val['occupation']['occupation_name'];
                if (isset($occupations_collect[$val['occupation']['id']]['count'])) {
                    $occupations_collect[$val['occupation']['id']]['count'] += 1;
                } else {
                    $occupations_collect[$val['occupation']['id']]['count'] = 1;
                }
            }
            // counting support company
            if ($val['support_company']) {
                $support_company_collect[$val['support_company']['id']]['name'] = $val['support_company']['support_company_name'];
                if (isset($support_company_collect[$val['support_company']['id']]['count'])) {
                    $support_company_collect[$val['support_company']['id']]['count'] += 1;
                } else {
                    $support_company_collect[$val['support_company']['id']]['count'] = 1;
                }
            }
            // counting supported company
            if ($val['supported_company']) {
                $supported_company_collect[$val['supported_company']['id']]['name'] = $val['supported_company']['supported_company_name'];
                if (isset($supported_company_collect[$val['supported_company']['id']]['count'])) {
                    $supported_company_collect[$val['supported_company']['id']]['count'] += 1;
                } else {
                    $supported_company_collect[$val['supported_company']['id']]['count'] = 1;
                }
            }
        }
        // 運転・同乗
        $ride_arr            = ["運転", "同乗"];
        $driver_ride_collect = [];
        foreach ($ride_arr as $key => $val) {
            $drive_count = DakouData::whereYear('target_date', $year)
                ->whereMonth('target_date', $month)
                ->where('dp_user', $user_id)
                ->where('dp_ride_flg', $val)
                ->count();
            $driver_ride_collect[$key]['name']  = $val;
            $driver_ride_collect[$key]['count'] = $drive_count;
        }
        $work_content              = array_values($work_content);
        $occupations_collect       = array_values($occupations_collect);
        $support_company_collect   = array_values($support_company_collect);
        $supported_company_collect = array_values($supported_company_collect);
        $driver_ride_collect       = array_values($driver_ride_collect);

        return [
            'collect_days'              => $collect_days_data ?? null,
            'collect_times'             => $collect_times_data ?? null,
            'collect_other_flag_count'  => $attendCountArr ?? null,
            'work_content'              => $work_content ?? null,
            'occupations_collect'       => $occupations_collect ?? null,
            'support_company_collect'   => $support_company_collect ?? null,
            'supported_company_collect' => $supported_company_collect ?? null,
            'driver_ride_collect'       => $driver_ride_collect ?? null
        ];
    }

    public function exportExcel(Request $request)
    {
        set_time_limit(0);
        ini_set('post_max_size', '4096M');
        $filter  = $request->input('filter');
        $type    = $request->input('type');    // single or all
        $date    = $filter['date'];
        $user_id = $filter['user'];
        $year        = date('Y', strtotime($date));
        $month       = date('n', strtotime($date));

        $attended_users_ = DakouData::whereYear('target_date',  $year)->whereMonth('target_date', $month)->groupBy('dp_user')->orderBy('dp_user', 'asc')->pluck('dp_user');
        if ($type == 'all') {
            $attended_users = $attended_users_?->toArray();
        } else {
            $attended_users = [$user_id];
        }


        $temp_file   = base_path('template/monthly_collect.xlsx');
        $spreadsheet = IOFactory::load($temp_file);

        if (!empty($attended_users) && $attended_users != null) {
            foreach ($attended_users as $user_index => $user) {
                // In addition, getting the organization information | modified: 2024/11/18
                $user_data = UserData::where('user_id', $user)->first();
                $org = $user_data->break_times->organization;
                $parent_org = $org->parentOrg;

                $daily_data   = $this->get_monthly_data($date, $user);
                $collect_data = $this->get_monthly_collect_data($request, $daily_data, $user);

                $working_days_collect      = $collect_data['collect_days'];
                $working_hours_collect     = $collect_data['collect_times'];
                $occp_collect              = $collect_data['occupations_collect'];
                $work_content_collect      = $collect_data['work_content'];
                $support_company_collect   = $collect_data['support_company_collect'];
                $supported_company_collect = $collect_data['supported_company_collect'];
                $attend_stauts_collect     = $collect_data['collect_other_flag_count'];
                $driving_collect           = $collect_data['driver_ride_collect'];

                $user_info   = User::find($user);
                $user_name   = $user_info->name;
                $user_code   = $user_info->code;
                $sheets      = $spreadsheet->getAllSheets();
                $orign_sheet = $sheets[0];

                if ($user_index > 0) {
                    $newSheet = clone $orign_sheet;
                    $newSheet->setTitle(($user_index + 1) . ".$user_code-$user_name"); //Change the SheetName;
                    $spreadsheet->addSheet($newSheet);
                    $sheet = $newSheet;
                } else {
                    $sheet = $orign_sheet;
                    $orign_sheet->setTitle(($user_index + 1) . ".$user_code-$user_name"); //Change the SheetName;
                }

                // Header title
                $jp_year = Helper::getJpDate($date, 'year');
                $title   = sprintf("月次出勤簿 - %s／%d年%d月度 %s)", $user_name, $year, $month, $jp_year);

                // Organization Info, B2~B4
                $organization_name = $parent_org ? $parent_org?->organization_name : $org?->organization_name;
                $organization_address = sprintf(
                    "〒%s %s",
                    $parent_org?->organization_zipcode ? $parent_org?->organization_zipcode : $org?->organization_zipcode,
                    $parent_org?->organization_address ? $parent_org?->organization_address : $org?->organization_address
                );
                $organization_master = $parent_org?->organization_master_name ? $parent_org?->organization_master_name : $org?->organization_master_name;

                $sheet->setCellValue('B2',  $organization_name);
                $sheet->setCellValue('B3',  $organization_address);
                $sheet->setCellValue('B4',  $organization_master);
                $sheet->setCellValue('B6', $title);

                $border_style = [
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
                    'font' => [
                        'size' => 7,
                        'name' => 'ＭＳ Ｐ明朝'
                    ],
                    // 'row_dimensions' => [
                    //     1 => ['height' => 20]
                    // ]
                ];

                // 労働日数
                $sheet->setCellValue('G9', $working_days_collect['rodo']);
                $sheet->setCellValue('G10', $working_days_collect['syukkin']);
                $sheet->setCellValue('G11', $working_days_collect['paid_holiday_syukkin']);
                $sheet->setCellValue('G12', $working_days_collect['kekkin']);
                $sheet->setCellValue('G13', $working_days_collect['sotai']);
                $sheet->setCellValue('M9', $working_days_collect['jitsudo']);
                $sheet->setCellValue('M10', $working_days_collect['holiday_syukkin']);
                $sheet->setCellValue('M11', $working_days_collect['sunday_syukkin_data']);
                $sheet->setCellValue('M12', $working_days_collect['chikoku']);

                // 労働時間
                $sheet->setCellValue('U9', $working_hours_collect['real_working_times']);
                $sheet->setCellValue('AA9', $working_hours_collect['working_times']);
                $sheet->setCellValue('U10', $working_hours_collect['holiday_working_times']);
                $sheet->setCellValue('AA10', $working_hours_collect['midight_times']);
                $sheet->setCellValue('U11', $working_hours_collect['midight_times']);
                $sheet->setCellValue('AA11', $working_hours_collect['holiday_midnight_times']);
                $sheet->setCellValue('U12', $working_hours_collect['real_over_times']);
                $sheet->setCellValue('AA12', $working_hours_collect['over_times']);
                $sheet->setCellValue('U13', $working_hours_collect['holiday_over_times']);

                // 運転・同乗
                $sheet->setCellValue('F16', $driving_collect[0]['count']);
                $sheet->setCellValue('L16', $driving_collect[1]['count']);

                // 残業、遅刻、早退、有給、研修など P~AB
                $second_col_skip_rows = 15;
                if (!empty($attend_stauts_collect)) {
                    foreach ($attend_stauts_collect as $key => $val) {
                        // label cell
                        $sheet->setCellValue($key % 2 == 0 ? 'P' . floor($key / 2) + $second_col_skip_rows + 1 : 'W' . floor($key / 2) + $second_col_skip_rows + 1, $val['name']);
                        $sheet->mergeCells('P' . (floor($key / 2) + $second_col_skip_rows + 1) . ':T' . (floor($key / 2) + $second_col_skip_rows + 1));
                        $sheet->mergeCells('W' . (floor($key / 2) + $second_col_skip_rows + 1) . ':Z' . (floor($key / 2) + $second_col_skip_rows + 1));

                        // count cell
                        $sheet->setCellValue($key % 2 == 0 ? 'U' . floor($key / 2) + $second_col_skip_rows + 1 : 'AA' . floor($key / 2) + $second_col_skip_rows + 1, $val['count']);
                        $sheet->mergeCells('U' . (floor($key / 2) + $second_col_skip_rows + 1) . ':V' . (floor($key / 2) + $second_col_skip_rows + 1));
                        $sheet->mergeCells('AA' . (floor($key / 2) + $second_col_skip_rows + 1) . ':AB' . (floor($key / 2) + $second_col_skip_rows + 1));
                    }
                    $sheet->getStyle('P15:AB' . (ceil(count($attend_stauts_collect) / 2) + $second_col_skip_rows))->applyFromArray($border_style);
                    $second_col_skip_rows += ceil(count($attend_stauts_collect) / 2);
                } else {
                    $sheet->mergeCells('P16:AB16');
                    $sheet->setCellValue('P16', 'データはありません。');
                    $sheet->getStyle('P16:AB16')->applyFromArray($border_style);
                    $second_col_skip_rows += 3;
                }

                // skip rows for under columns

                // 応援に行った OR 応援に来てもらった AD~AZ
                // 応援に来てもらった
                $third_col_skip_rows = 8;
                $sheet->setCellValue('AD8', '応援に来てもらった');
                if (!empty($support_company_collect)) {
                    foreach ($support_company_collect as $key => $val) {
                        // label cell AD~AL, AO~AX
                        $sheet->mergeCells('AD' . (floor($key / 2) + $third_col_skip_rows + 1) . ':AM' . (floor($key / 2) + $third_col_skip_rows + 1));
                        $sheet->mergeCells('AP' . (floor($key / 2) + $third_col_skip_rows + 1) . ':AX' . (floor($key / 2) + $third_col_skip_rows + 1));
                        $sheet->setCellValue($key % 2 == 0 ? 'AD' . (floor($key / 2) + $third_col_skip_rows + 1) : 'AP' . (floor($key / 2) + $third_col_skip_rows + 1), $val['name']);

                        // count cell AM~AN, AY~AZ
                        $sheet->mergeCells('AN' . (floor($key / 2) + $third_col_skip_rows + 1) . ':AO' . (floor($key / 2) + $third_col_skip_rows + 1));
                        $sheet->mergeCells('AY' . (floor($key / 2) + $third_col_skip_rows + 1) . ':AZ' . (floor($key / 2) + $third_col_skip_rows + 1));
                        $sheet->setCellValue($key % 2 == 0 ? 'AN' . (floor($key / 2) + $third_col_skip_rows + 1) : 'AY' . (floor($key / 2) + $third_col_skip_rows + 1), $val['count']);
                        // border
                    }
                    $sheet->getStyle('AD8:AZ' . (ceil(count($support_company_collect) / 2) + $third_col_skip_rows))->applyFromArray($border_style);
                    $third_col_skip_rows += ceil(count($support_company_collect) / 2) + 2;
                } else {
                    $sheet->mergeCells('AD8:AZ8');
                    $sheet->setCellValue('AD8', "データはありません。");
                    $sheet->getStyle('AD8:AZ' . (ceil(count($support_company_collect) / 2) + $third_col_skip_rows))->applyFromArray($border_style);
                    $third_col_skip_rows += 3;
                }

                // 応援に行った
                $sheet->mergeCells('AD' . $third_col_skip_rows . ':AZ' . $third_col_skip_rows);
                $sheet->setCellValue('AD' . ($third_col_skip_rows), '応援に行った');
                if (!empty($supported_company_collect)) {
                    foreach ($supported_company_collect as $key => $val) {
                        // label cell
                        $sheet->mergeCells('AD' . (floor($key / 2) + $third_col_skip_rows + 1) . ':AM' . (floor($key / 2) + $third_col_skip_rows + 1));
                        $sheet->mergeCells('AP' . (floor($key / 2) + $third_col_skip_rows + 1) . ':AX' . (floor($key / 2) + $third_col_skip_rows + 1));
                        $sheet->setCellValue($key % 2 == 0 ? 'AD' . (floor($key / 2) + $third_col_skip_rows + 1) : 'AP' . (floor($key / 2) + $third_col_skip_rows + 1), $val['name']);

                        // count cell
                        $sheet->mergeCells('AN' . (floor($key / 2) + $third_col_skip_rows + 1) . ':AO' . (floor($key / 2) + $third_col_skip_rows + 1));
                        $sheet->mergeCells('AY' . (floor($key / 2) + $third_col_skip_rows + 1) . ':AZ' . (floor($key / 2) + $third_col_skip_rows + 1));
                        $sheet->setCellValue($key % 2 == 0 ? 'AN' . (floor($key / 2) + $third_col_skip_rows + 1) : 'AY' . (floor($key / 2) + $third_col_skip_rows + 1), $val['count']);
                    }
                    $sheet->getStyle('AD' . ($third_col_skip_rows) . ':AZ' . ceil(count($supported_company_collect) / 2) + $third_col_skip_rows)->applyFromArray($border_style);
                    $third_col_skip_rows += ceil(count($supported_company_collect) / 2) + 2;
                } else {
                    $sheet->mergeCells('AD' . ($third_col_skip_rows + 1) . ':AZ' . ($third_col_skip_rows + 1));
                    $sheet->setCellValue('AD' . ($third_col_skip_rows + 1), "データはありません。");
                    $sheet->getStyle('AD' . ($third_col_skip_rows) . ':AZ' . ($third_col_skip_rows + 1))->applyFromArray($border_style);
                    $third_col_skip_rows += 3;
                }

                // 作業内容
                $sheet->mergeCells('AD' . $third_col_skip_rows . ':AZ' . $third_col_skip_rows);
                $sheet->setCellValue('AD' . ($third_col_skip_rows), '作業内容');
                $sheet->getRowDimension($third_col_skip_rows)->setRowHeight(15);

                if (!empty($work_content_collect)) {
                    foreach ($work_content_collect as $key => $val) {
                        // label cell
                        $sheet->mergeCells('AD' . (floor($key / 2) + $third_col_skip_rows + 1) . ':AM' . (floor($key / 2) + $third_col_skip_rows + 1));
                        $sheet->mergeCells('AP' . (floor($key / 2) + $third_col_skip_rows + 1) . ':AX' . (floor($key / 2) + $third_col_skip_rows + 1));
                        $sheet->setCellValue($key % 2 == 0 ? 'AD' . (floor($key / 2) + $third_col_skip_rows + 1) : 'AP' . (floor($key / 2) + $third_col_skip_rows + 1), $val['name']);

                        // count cell
                        $sheet->mergeCells('AN' . (floor($key / 2) + $third_col_skip_rows + 1) . ':AO' . (floor($key / 2) + $third_col_skip_rows + 1));
                        $sheet->mergeCells('AY' . (floor($key / 2) + $third_col_skip_rows + 1) . ':AZ' . (floor($key / 2) + $third_col_skip_rows + 1));
                        $sheet->setCellValue($key % 2 == 0 ? 'AN' . (floor($key / 2) + $third_col_skip_rows + 1) : 'AY' . (floor($key / 2) + $third_col_skip_rows + 1), $val['count']);
                    }
                    $sheet->getStyle('AD' . ($third_col_skip_rows) . ':AZ' . ceil(count($work_content_collect) / 2) + $third_col_skip_rows)->applyFromArray($border_style);
                    $third_col_skip_rows += ceil(count($work_content_collect) / 2) + 2;
                } else {
                    $sheet->mergeCells('AD' . ($third_col_skip_rows + 1) . ':AZ' . ($third_col_skip_rows + 1));
                    $sheet->setCellValue('AD' . ($third_col_skip_rows + 1), "データはありません。");
                    $sheet->getStyle('AD' . ($third_col_skip_rows) . ':AZ' . ($third_col_skip_rows + 1))->applyFromArray($border_style);
                    $third_col_skip_rows += 3;
                }

                // 職種別集計の表示 AD~AZ
                // start_rows is $other_flg_rows + 1 + (initial row numbers)
                $sheet->mergeCells('AD' . ($third_col_skip_rows) . ':AZ' . ($third_col_skip_rows));
                $sheet->setCellValue('AD' . ($third_col_skip_rows), '職種');
                $sheet->getRowDimension($third_col_skip_rows)->setRowHeight(15);
                $sheet->getStyle('AD' . ($third_col_skip_rows + 1))->applyFromArray($border_style);
                $sheet->getRowDimension($third_col_skip_rows + 1)->setRowHeight(15);
                if (!empty($occp_collect)) {
                    // merge header cells
                    foreach ($occp_collect as $key => $val) {
                        // merge and set label field
                        $sheet->mergeCells('AD' . ($third_col_skip_rows + floor($key / 2) + 1) . ':AL' . ($third_col_skip_rows + floor($key / 2) + 1));
                        $sheet->mergeCells('AO' . ($third_col_skip_rows + floor($key / 2) + 1) . ':AX' . ($third_col_skip_rows + floor($key / 2) + 1));
                        $sheet->setCellValue($key % 2 == 0 ? 'AD' . (floor($key / 2) + $third_col_skip_rows + 1) : 'AO' . (floor($key / 2) + $third_col_skip_rows + 1), $val['name']);

                        // merge and set value field
                        $sheet->mergeCells('AM' . ($third_col_skip_rows + floor($key / 2) + 1) . ':AN' . ($third_col_skip_rows + floor($key / 2) + 1));
                        $sheet->mergeCells('AY' . ($third_col_skip_rows + floor($key / 2) + 1) . ':AZ' . ($third_col_skip_rows + floor($key / 2) + 1));
                        $sheet->setCellValue($key % 2 == 0 ? 'AM' . (floor($key / 2) + $third_col_skip_rows + 1) : 'AY' . (floor($key / 2) + $third_col_skip_rows + 1), $val['count']);
                    }
                    $sheet->getStyle('AD' . ($third_col_skip_rows) . ':AZ' . ($third_col_skip_rows + ceil(count($occp_collect) / 2)))->applyFromArray($border_style);
                    $third_col_skip_rows += ceil(count($occp_collect) / 2) + 2; // include header
                } else {
                    $sheet->mergeCells('AD' . ($third_col_skip_rows + 1) . ':AZ' . ($third_col_skip_rows + 1));
                    $sheet->setCellValue('AD' . ($third_col_skip_rows + 5), 'データはありません。');
                    $sheet->getStyle('AD' . ($third_col_skip_rows) . ':AZ' . ($third_col_skip_rows + 1))->applyFromArray($border_style);
                    $third_col_skip_rows += 3;
                }


                if ($second_col_skip_rows >= $third_col_skip_rows) {
                    $merge_skip_rows = $second_col_skip_rows;
                } else {
                    $merge_skip_rows = $third_col_skip_rows;
                }

                // Header of daily data
                // $header_rows = ["No", "日付", "曜日", "休日区分", "勤怠状況", "シフト", "出勤時刻", "退勤時刻", "労働時間", "実労働時間", "休憩時間", "残業"];
                for ($key = 0; $key <= count($daily_data); $key++) {
                    $sheet->mergeCells('B' . ($merge_skip_rows + $key) . ':C' . ($merge_skip_rows + $key)); // NO
                    $sheet->mergeCells('D' . ($merge_skip_rows + $key) . ':G' . ($merge_skip_rows + $key)); // D:G 日付
                    $sheet->mergeCells('H' . ($merge_skip_rows + $key) . ':I' . ($merge_skip_rows + $key)); //H:I 曜日day
                    $sheet->mergeCells('J' . ($merge_skip_rows + $key) . ':L' . ($merge_skip_rows + $key)); // J:L 休日区分 holiday
                    $sheet->mergeCells('M' . ($merge_skip_rows + $key) . ':O' . ($merge_skip_rows + $key)); //M:O 勤怠状況 status
                    $sheet->mergeCells('P' . ($merge_skip_rows + $key) . ':Q' . ($merge_skip_rows + $key));  // P:Q １日 半日
                    $sheet->mergeCells('R' . ($merge_skip_rows + $key) . ':V' . ($merge_skip_rows + $key)); // R:V シフト
                    $sheet->mergeCells('W' . ($merge_skip_rows + $key) . ':Z' . ($merge_skip_rows + $key)); // W:Z 出勤時刻
                    $sheet->mergeCells('AA' . ($merge_skip_rows + $key) . ':AD' . ($merge_skip_rows + $key)); //AA:AD 退勤時刻
                    $sheet->mergeCells('AE' . ($merge_skip_rows + $key) . ':AH' . ($merge_skip_rows + $key)); //AE: AH 労働時間 working
                    $sheet->mergeCells('AI' . ($merge_skip_rows + $key) . ':AL' . ($merge_skip_rows + $key)); // AI:AL 実労働時間
                    $sheet->mergeCells('AM' . ($merge_skip_rows + $key) . ':AP' . ($merge_skip_rows + $key)); // AM:AP 休憩時間
                    $sheet->mergeCells('AQ' . ($merge_skip_rows + $key) . ':AZ' . ($merge_skip_rows + $key)); // AQ: AZ  残業
                    $sheet->getRowDimension($merge_skip_rows + $key + 1)->setRowHeight(15);
                    if ($key == 0) {
                        // insert header
                        $sheet->setCellValue('B' . ($merge_skip_rows + $key), "No"); // NO
                        $sheet->setCellValue('D' . ($merge_skip_rows + $key), "日付"); // date
                        $sheet->setCellValue('H' . ($merge_skip_rows + $key), "曜日"); //day
                        $sheet->setCellValue('J' . ($merge_skip_rows + $key), "休日\n区分"); // kind
                        $sheet->setCellValue('M' . ($merge_skip_rows + $key), "勤怠\n状況"); //shift
                        $sheet->setCellValue('P' . ($merge_skip_rows + $key), "１日\n半日");  //syukkin
                        $sheet->setCellValue('R' . ($merge_skip_rows + $key), "シフト");  //syukkin
                        $sheet->setCellValue('W' . ($merge_skip_rows + $key), "出勤時刻"); //taikin
                        $sheet->setCellValue('AA' . ($merge_skip_rows + $key), "退勤時刻"); //working
                        $sheet->setCellValue('AE' . ($merge_skip_rows + $key), "労働時間"); //real working
                        $sheet->setCellValue('AI' . ($merge_skip_rows + $key), "実労働\n時間"); //res time
                        $sheet->setCellValue('AM' . ($merge_skip_rows + $key), "休憩時間"); //other flg
                        $sheet->setCellValue('AQ' . ($merge_skip_rows + $key), "残業"); //other flg
                        $sheet->getStyle('B' . $merge_skip_rows . ':AZ' . $merge_skip_rows)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E5E5');
                        $sheet->getRowDimension($merge_skip_rows + $key)->setRowHeight(22);
                        $sheet->getStyleByColumnAndRow(16, $merge_skip_rows + $key)->getAlignment()->setWrapText(true);
                    }
                    if ($key < count($daily_data)) {
                        $sheet->setCellValue('B' . ($merge_skip_rows + $key + 1), $key + 1); // NO
                        $sheet->setCellValue('D' . ($merge_skip_rows + $key + 1), $daily_data[$key]['date']); // date
                        $sheet->setCellValue('H' . ($merge_skip_rows + $key + 1), $daily_data[$key]['day']); //day
                        $sheet->setCellValue('J' . ($merge_skip_rows + $key + 1), $daily_data[$key]['holiday']); // kind
                        $sheet->setCellValue('M' . ($merge_skip_rows + $key + 1), $daily_data[$key]['type']); //勤怠状況
                        $sheet->setCellValue('P' . ($merge_skip_rows + $key + 1), $daily_data[$key]['dtype']);  //syukkin
                        if ($daily_data[$key]['id']) {
                            $sheet->setCellValue('R' . ($merge_skip_rows + $key + 1), $daily_data[$key]['shift']);  //syukkin
                            $sheet->setCellValue('AE' . ($merge_skip_rows + $key + 1), $daily_data[$key]['shift_times']); //real working
                        }
                        $sheet->setCellValue('W' . ($merge_skip_rows + $key + 1), $daily_data[$key]['syukin']); //シフト
                        $sheet->setCellValue('AA' . ($merge_skip_rows + $key + 1), $daily_data[$key]['taikin']); //working
                        $sheet->setCellValue('AI' . ($merge_skip_rows + $key + 1), $daily_data[$key]['work_time']); //res time
                        $sheet->setCellValue('AM' . ($merge_skip_rows + $key + 1), $daily_data[$key]['rest_time']); //other flg
                        $sheet->setCellValue('AQ' . ($merge_skip_rows + $key + 1), $daily_data[$key]['other_flg']); //other flg
                        // Format
                        if ($daily_data[$key]['day'] == "日") {
                            $sheet->getStyle('H' . ($merge_skip_rows + $key + 1) . ':I' . ($merge_skip_rows + $key + 1))->getFont()->setColor(new Color(Color::COLOR_RED));
                        } else if ($daily_data[$key]['day'] == "土") {
                            $sheet->getStyle('H' . ($merge_skip_rows + $key + 1) . ':I' . ($merge_skip_rows + $key + 1))->getFont()->setColor(new Color(Color::COLOR_BLUE));
                        }
                        if ($daily_data[$key]['holiday'] == "休日") {
                            $sheet->getStyle('H' . ($merge_skip_rows + $key + 1) . ':I' . ($merge_skip_rows + $key + 1))->getFont()->setColor(new Color(Color::COLOR_RED));
                            $sheet->getStyle('B' . ($merge_skip_rows + $key + 1) . ':AZ' . ($merge_skip_rows + $key + 1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('E6DF9C');
                        } else if ($daily_data[$key]['holiday'] == "有給") {
                            $sheet->getStyle('H' . ($merge_skip_rows + $key + 1) . ':I' . ($merge_skip_rows + $key + 1))->getFont()->setColor(new Color(Color::COLOR_RED));
                            $sheet->getStyle('H' . ($merge_skip_rows + $key + 1) . ':I' . ($merge_skip_rows + $key + 1))->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color(Color::COLOR_YELLOW));
                        }
                        // ・稼働日に打刻がない行を、ピンクなどで色付けして欲しい
                        if (!in_array($daily_data[$key]['day'], ["土", "日"]) && $daily_data[$key]['holiday'] == "" && $daily_data[$key]['status'] == false) {
                            $sheet->getStyle('B' . ($merge_skip_rows + $key + 1) . ':AZ' . ($merge_skip_rows + $key + 1))->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('E1A9E8'));
                        } else {
                            if ($daily_data[$key]['is_before'] == 'lt' && !in_array($daily_data[$key]['day'], ["土", "日"])) {
                                $sheet->getStyle('B' . ($merge_skip_rows + $key + 1) . ':AZ' . ($merge_skip_rows + $key + 1))->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('92D1E8'));
                            }
                        }
                    }
                }
                $border_style['font'] = ['size' => 8];
                $sheet->getStyle('B' . ($merge_skip_rows) . ':AZ' . ($merge_skip_rows + count($daily_data)))->applyFromArray($border_style);
            }
        }

        $new_excel = new Xlsx($spreadsheet);
        $path = public_path('storage/collecting');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $fname = sprintf("月次出勤簿_%d年%d月度.xlsx", $year, $month);

        $new_excel->save($path . '/' . $fname);
        return response()->json(['path' => 'collecting/' . $fname]);
    }
}
