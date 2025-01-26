<?php

namespace App\Http\Controllers\Admin\Master;

use App\Helper\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CsvFileRequest;
use App\Http\Requests\DakokuRequest;
use App\Imports\AttendDataImport;
use App\Models\{WorkLocation, WorkContent, User, SupportedCompany, Occupation, DakouData, AttendStatus, AttendType, CompanySetting, DakouChild, Organization, SupportCompany, TimeZone};
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $key        = $request->input("key");
        $sdate      = $request->input("sdate");
        $cdate      = $request->input("cdate");
        $user       = $request->input("user");
        $type       = $request->input("type");
        $start      = $request->input("start");
        $close      = $request->input("close");
        $drive      = $request->input("drive");
        $overtime   = $request->input("overtime");
        $memo       = $request->input("memo");
        $sc         = $request->input("sc");
        $sec        = $request->input("sec");
        $occp       = $request->input("occp");
        $wcon       = $request->input("wcon");
        $loc        = $request->input("loc");
        $tz         = $request->input("tz");
        $org        = $request->input("org");
        $attendance = DakouData::with([
            'attend_type',
            'dakoku_children' => function ($query) use ($loc, $occp, $wcon, $tz) {
                if($loc) {
                    $query->where('dp_work_location_id', $loc);
                }
                if($occp) {
                    $query->where('dp_occupation_id', $occp);
                }
                if($wcon) {
                    $query->where('dp_work_contens_id', $wcon);
                }
                if($tz) {
                    $query->where('dp_timezone_id', $tz);
                }
            },
            'user.user_data.break_times.organization',
            'dakoku_children.support_company',
            'dakoku_children.supported_company',
            'dakoku_children.occupation',
            'dakoku_children.work_content',
            'dakoku_children.work_location',
            'dakoku_children.worker_master',
            'dakoku_children.timezone',
            'attend_status'
        ])->when($key, function ($query) use ($key) {
            return $query->where(function ($q) use ($key) {
                $q->orWhere('dp_memo', 'like', "%" . $key . "%");
                $q->orWhere('dp_ride_flg', 'like', "%" . $key . "%");
                $q->orWhereHas("attend_type", function ($sq) use ($key) {
                    $sq->where("attend_type_name", "like", "%" . $key . "%");
                });
                $q->orWhereHas("user", function ($sq) use ($key) {
                    $sq->where("name", "like", "%" . $key . "%");
                });
                $q->orWhereHas("attend_status", function ($sq) use ($key) {
                    $sq->where("attend_name", "like", "%" . $key . "%");
                });
                $q->orWhereHas("dakoku_children.support_company", function ($sq) use ($key) {
                    $sq->where("support_company_name", "like", "%" . $key . "%");
                });
                $q->orWhereHas("dakoku_children.supported_company", function ($sq) use ($key) {
                    $sq->where("supported_company_name", "like", "%" . $key . "%");
                });
                $q->orWhereHas("dakoku_children.work_content", function ($sq) use ($key) {
                    $sq->where("work_content_name", "like", "%" . $key . "%");
                });
                $q->orWhereHas("dakoku_children.work_location", function ($sq) use ($key) {
                    $sq->where("location_name", "like", "%" . $key . "%");
                });
                $q->orWhereHas("dakoku_children.timezone", function ($sq) use ($key) {
                    $sq->where("detail_times", "like", "%" . $key . "%");
                });
            });
        })->when($sdate, function ($q) use ($sdate) {
            $q->whereDate("target_date", ">=", $sdate);
        })->when($cdate, function ($q) use ($cdate) {
            $q->whereDate("target_date", "<=", $cdate);
        })->when($user, function ($q) use ($user) {
            $q->where("dp_user",  $user);
        })->when($type, function ($q) use ($type) {
            $q->where("dp_status", $type);
        })->when($start, function ($q) use ($start) {
            $q->whereTime("dp_syukkin_time", '>=', $start);
        })->when($close, function ($q) use ($close) {
            $q->whereTime("dp_taikin_time", '<=', $close);
        })->when($close, function ($q) use ($close) {
            $q->whereTime("dp_taikin_time", '<=', $close);
        })->when($drive, function ($q) use ($drive) {
            $q->where("dp_ride_flg", $drive);
        })->when($overtime, function ($q) use ($overtime) {
            $q->where("dp_other_flg", $overtime);
        })->when($memo, function ($q) use ($memo) {
            $q->where("dp_memo", 'like',  "%" . $memo . "%");
        })->when($sc, function ($q) use ($sc) {
            $q->whereHas("dakoku_children", function ($sq) use ($sc) {
                $sq->where("dp_support_company_id", $sc);
            });
        })->when($sec, function ($q) use ($sec) {
            $q->whereHas("dakoku_children", function ($sq) use ($sec) {
                $sq->where("dp_supported_company_id", $sec);
            });
        })->when($occp, function ($q) use ($occp) {
            $q->whereHas("dakoku_children", function ($sq) use ($occp) {
                $sq->where("dp_occupation_id", $occp);
            });
        })->when($wcon, function ($q) use ($wcon) {
            $q->whereHas("dakoku_children", function ($sq) use ($wcon) {
                $sq->where("dp_work_contens_id", $wcon);
            });
        })->when($loc, function ($q) use ($loc) {
            $q->whereHas("dakoku_children", function ($sq) use ($loc) {
                $sq->where("dp_work_location_id", $loc);
            });
        })->when($tz, function ($q) use ($tz) {
            $q->whereHas("dakoku_children", function ($sq) use ($tz) {
                $sq->where("dp_timezone_id", $tz);
            });
        })->when($org, function ($q) use ($org) {
            $q->whereHas("user.user_data.break_times.organization", function ($sq) use ($org) {
                $sq->where("id", $org);
            });
        })->orderBy('target_date', 'DESC')->orderBy('created_at', 'DESC');
        $idArray           = $attendance->pluck('id');
        $attendance        = $attendance->paginate(50)->withQueryString();
        $users             = User::users();
        $attend_type       = AttendType::all();
        $attend_status     = AttendStatus::all();
        $support_company   = SupportCompany::all();
        $supported_company = SupportedCompany::all();
        $occupation        = Occupation::all();
        $work_contents     = WorkContent::all();
        $locations         = WorkLocation::where("location_flag", 1)->get();
        $timezones         = TimeZone::all();
        $organization      = Organization::all();
        $close_status      = CompanySetting::first()?->company_month_closing_status;
        $close_month       = CompanySetting::first()?->company_month_closing_date;
        return Inertia::render('Admin/Master/Attendance/AttendanceIndex', compact('attendance', 'idArray', 'users', 'attend_type', 'attend_status', 'support_company', 'supported_company', 'occupation', 'work_contents', 'locations', 'timezones', 'organization', 'close_status', 'close_month'));
    }
    public function create()
    {
        $attend_type       = AttendType::all();
        $support_company   = SupportCompany::all();
        $supported_company = SupportedCompany::all();
        $attend_status     = AttendStatus::all();
        $occupation        = Occupation::all();
        $work_contents     = WorkContent::all();
        $work_locations    = WorkLocation::where("location_flag", 1)->get();
        $timezones         = TimeZone::all();
        $users             = User::users();
        $close_status      = CompanySetting::first()?->company_month_closing_status;
        $close_month       = CompanySetting::first()?->company_month_closing_date;
        return Inertia::render('Admin/Master/Attendance/CreateAttendance', compact(
            'attend_type',
            'attend_status',
            'support_company',
            'supported_company',
            'occupation',
            'work_contents',
            'work_locations',
            'timezones',
            'users',
            'close_status',
            'close_month',
        ));
    }
    public function checkExistDate(Request $request)
    {
        $user_id     = $request->input('id');
        $target_date = $request->input('date');
        $exist_check = DakouData::whereDate('target_date', $target_date)->where('dp_user', $user_id)->exists();
        if ($exist_check) {
            return response()->json(['success' => true, 'message' => '選択した日付の打刻データが既に存在します。']);
        }
    }
    public function store(DakokuRequest $request)
    {
        $request->validate(
            [
                'syukkinTime' => 'required',
                'taikinTime'  => 'required',
            ],
            [
                'syukkinTime.required' => '出勤時間は必須項目です。',
                'taikinTime.required'  => '退勤時刻は必須項目です。',
            ],
        );

        $attendance_id = null;

        // 出勤登録をせずに退勤登録をする場合

        DB::transaction(function () use ($request, &$attendance_id) {
            $date     = $request->targetDate;
            $user     = $request->user;
            $type     = $request->attendType;
            $dp_type  = $request->dpType;
            $syukkin  = $request->syukkinTime;
            $taikin   = $request->taikinTime;
            $ride     = $request->driveRide;
            $other    = $request->otherFlag;
            $memo     = $request->memo;
            $address  = $request->address;
            $children = $request->children;
            // commit to Table

            $data = [
                'target_date'      => Carbon::parse($date)->format('Y-m-d'),
                'dp_user'          => $user['id'],
                'dp_status'        => $type,
                'dp_type'          => $dp_type,
                'dp_syukkin_time'  => $syukkin,
                'dp_taikin_time'   => $taikin,
                'dp_ride_flg'      => $ride,
                'dp_other_flg'     => $other,
                'dp_memo'          => $memo,
                'dp_dakou_address' => $address,
                'dp_made_by'       => auth()->user()->id,
            ];

            if ($type == 1) {
                $data['dp_syukkin_time'] = $syukkin;
                $data['dp_taikin_time']  = null;
            } else {
                $data['dp_syukkin_time'] = $syukkin;
                $data['dp_taikin_time']  = $taikin;
            }
            $attendance = DakouData::create($data);
            $attendance_id = $attendance->id;

            if (!empty($children) && $attendance->id && $attendance->dp_user == $user['id']) {
                foreach ($children as $item) {
                    DakouChild::create([
                        'dp_dakoku_id'            => $attendance->id,
                        'dp_occupation_id'        => $item['occupation'],
                        'dp_work_contens_id'      => $item['workContent'],
                        'dp_work_location_id'     => $item['location'],
                        'dp_support_flg'          => $item['supportFlag'],
                        'dp_support_company_id'   => $item['supportFlag'] == 2 ? $item['supportCompany'] : null,
                        'dp_supported_company_id' => $item['supportFlag'] == 1 ? $item['supportedCompany'] : null,
                        'dp_nums_of_people'       => $item['supportFlag'] != 0 ? $item['peopleNums'] : null,
                        'dp_timezone_id'          => $item['timezone'],
                        'dp_unique_counter'       => $item['uniqueCounter'],
                    ]);
                }
                Cache::flush();
            }
        });
        $redirect_option = $request->redirectOption;
        if ($redirect_option == 1) {
            return redirect()->route('admin.master.attendance.index');
        } else if ($redirect_option == 2) {
            return redirect()->route('admin.master.attendance.show', ['id' => $attendance_id]);
        } else {
            return redirect()->route('admin.master.attendance.create');
        }
    }
    public function show($id)
    {
        $users = User::users();
        $attendanceDetail = DakouData::with([
            'attend_type',
            'dakoku_children',
            'user',
            'attend_status',
            'dakoku_children.support_company',
            'dakoku_children.supported_company',
            'dakoku_children.occupation',
            'dakoku_children.work_content',
            'dakoku_children.work_location',
            'dakoku_children.worker_master',
            'dakoku_children.timezone',
        ])->orderBy('id', 'DESC')->find($id);
        return Inertia::render('Admin/Master/Attendance/AttendanceDetail', compact('attendanceDetail', 'users'));
    }
    public function edit($id)
    {
        $close_status = CompanySetting::first()?->company_month_closing_status;
        $close_month   = CompanySetting::first()?->company_month_closing_date;
        $dakoku_date  = DakouData::where('id', $id)->first()?->target_date;
        if ($close_status) {
            if (date('Y-m', strtotime($dakoku_date)) <= date('Y-m', strtotime($close_month))) {
                return redirect()->route('admin.master.attendance.index');
            }
        }
        $attend_type       = AttendType::all();
        $support_company   = SupportCompany::all();
        $supported_company = SupportedCompany::all();
        $attend_status     = AttendStatus::all();
        $occupation        = Occupation::all();
        $work_contents     = WorkContent::all();
        $work_locations    = WorkLocation::where("location_flag", 1)->get();
        $users             = User::users();
        $timezones         = TimeZone::all();
        $info              = DakouData::with([
            'dakoku_children',
            'dakoku_children.support_company',
            'dakoku_children.supported_company',
            'dakoku_children.occupation',
            'dakoku_children.work_content',
            'dakoku_children.work_location',
            'dakoku_children.timezone',
        ])->find($id);
        return Inertia::render('Admin/Master/Attendance/AttendanceEdit', compact(
            'attend_type',
            'support_company',
            'supported_company',
            'attend_status',
            'occupation',
            'work_contents',
            'work_locations',
            'users',
            'timezones',
            'info',
            'close_status',
            'close_month',
        ));
    }
    public function update(DakokuRequest $request)
    {
        DB::transaction(function () use ($request) {
            $id      = $request->id;
            $date    = $request->targetDate;
            $user    = $request->user;
            $type    = $request->attendType;
            $dpType  = $request->dpType;
            $syukkin = $request->syukkin;
            $taikin  = $request->taikin;
            $ride    = $request->driveRide;
            $other   = $request->otherFlag;
            $memo    = $request->memo;
            $address = $request->address;

            $children        = $request->children;
            $update_data = [
                'target_date'               => Carbon::parse($date)->format('Y-m-d'),
                // 'dp_user'                   => $user['id'],
                'dp_status'        => $type,
                'dp_type'          => $dpType,
                'dp_syukkin_time'  => $syukkin,
                'dp_taikin_time'   => $taikin,
                'dp_ride_flg'      => $ride,
                'dp_other_flg'     => $other,
                'dp_memo'          => $memo,
                'dp_dakou_address' => $address,
                'dp_made_by'       => auth()->user()->id,
            ];

            // $parent = DakouData::where('id', $id)->update($update_data);
            $parent = DakouData::updateOrCreate(['id' => $id, 'dp_user' => $user['id']], $update_data);
            if (!empty($children) && $id) {
                $old_dakou_children = DakouData::find($id);
                $old_child_id_arr = $old_dakou_children->dakoku_children->collect()->map(function ($item) {
                    return $item['id'];
                })->toArray();
                $new_children_id_arr = [];
                foreach ($children as $item) {
                    $new_children_id_arr[] = $item['id'];
                }
                // remove deleted child
                $id_arr_to_remove = array_diff($old_child_id_arr, $new_children_id_arr);

                // step 1: remove when delete by user
                DakouChild::whereIn('id', $id_arr_to_remove)->delete();

                // step 2: add new children
                if ($user['id'] == $parent->dp_user) {
                    foreach ($children as $item) {
                        // when is $item['id'] == null, this case mean added new child
                        // so that, in this case have to add new child dakoku data
                        // however, $child_id_arr are not include $item['id], this means remove the child dakoku data
                        DakouChild::updateOrCreate(
                            [
                                'id' => $item['id']
                            ],
                            [
                                'dp_dakoku_id'            => $id,
                                'dp_support_flg'          => $item['supportFlag'],
                                'dp_support_company_id'   => $item['supportFlag'] == 2 ? $item['supportCompany'] : null,
                                'dp_supported_company_id' => $item['supportFlag'] == 1 ? $item['supportedCompany'] : null,
                                'dp_nums_of_people'       => $item['supportFlag'] != 0 ? $item['peopleNums'] : null,
                                'dp_occupation_id'        => $item['occupation'],
                                'dp_work_contens_id'      => $item['workContent'],
                                'dp_work_location_id'     => $item['location'],
                                'dp_timezone_id'          => $item['timezone'],
                                'dp_unique_counter'       => $item['uniqueCounter'],
                            ]
                        );
                    }
                }
            }
        });
        $redirect_option = $request->redirectOption;
        if ($redirect_option == 1) {
            return redirect()->route('admin.master.attendance.index');
        } else if ($redirect_option == 2) {
            return redirect()->route('admin.master.attendance.show', ['id' => $request->id]);
        } else {
            return redirect()->route('admin.master.attendance.create');
        }
    }

    public function updateRow(Request $request)
    {
        $data            = $request->input('data');
        $id              = $data['id'];
        $dp_status       = $data['attend_type']['id'];
        $dp_type         = $data['dp_type'];
        $dp_syukkin_time = $data['dp_syukkin_time'];
        $dp_taikin_time  = $data['dp_taikin_time'];
        $dp_ride_flg     = $data['dp_ride_flg'];
        $dp_other_flg    = $data['attend_status']['id'] ?? null;
        $dp_memo         = $data['dp_memo'];
        $child           = $data['dakoku_children'];

        DB::beginTransaction();
        try {
            DakouData::find($id)->update([
                'dp_status'       => $dp_status,
                'dp_type'         => $dp_type,
                'dp_syukkin_time' => $dp_syukkin_time,
                'dp_taikin_time'  => $dp_taikin_time,
                'dp_ride_flg'     => $dp_ride_flg,
                'dp_other_flg'    => $dp_other_flg,
                'dp_memo'         => $dp_memo,
            ]);
            if($child !== null) {
                foreach($child as $val) {
                    $c_id                = $val['id'];
                    $dp_timezone_id      = $val['timezone']['id'] ?? null;
                    $dp_occupation_id    = $val['occupation']['id'] ?? null;
                    $dp_work_contens_id  = $val['work_content']['id'] ?? null;
                    $dp_work_location_id = $val['work_location']['id'] ?? null;
                    $dp_nums_of_people = $val['dp_nums_of_people'];
                    DakouChild::find($c_id)->update([
                        'dp_timezone_id'      => $dp_timezone_id,
                        'dp_occupation_id'    => $dp_occupation_id,
                        'dp_work_contens_id'  => $dp_work_contens_id,
                        'dp_work_location_id' => $dp_work_location_id,
                        'dp_nums_of_people'   => $dp_nums_of_people,
                    ]);
                }
            }
            DB::commit();
            return response()->json(['success' => true, 'error' => false]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
        dd($data);
    }
    public function destroy(Request $request)
    {
        if ($request) {
            DakouData::where('id', $request->id)->delete();
            Cache::flush();
            return redirect()->route('admin.master.attendance.index');
        }
    }
    public function getTimeSet(Request $request)
    {
        $user_id = $request->id;
        $timeSet = User::find($user_id)->user_data?->break_times;
        return response()->json($timeSet);
    }
    public function export(Request $request)
    {
        try {
            $type             = $request->input('type');
            $id_array         = $request->input('ids');
            $file_name_format = "出勤管理_%d.%s";
            $file_name        = sprintf($file_name_format, date('YmdHis'), $type);
            $path             = 'master/' . $file_name;
            $dakou_data       = DakouData::query();

            $csv_data = [];
            $dakou_data_include_child = $dakou_data->with('dakoku_children')->whereIn('id', $id_array)->orderBy('dp_user', 'asc')->orderBy('target_date', 'asc')->get();

            $i = 0;
            foreach ($dakou_data_include_child as $key => $val) {
                $csv_data[$i][0]  = $val->id;
                $csv_data[$i][1]  = Carbon::parse($val->target_date)->format('Y-m-d');
                $csv_data[$i][2]  = $val->dp_user;
                $csv_data[$i][3]  = $val->dp_status;
                $csv_data[$i][4]  = $val->dp_syukkin_time;
                $csv_data[$i][5]  = $val->dp_taikin_time;
                $csv_data[$i][6]  = $val->dp_break_start_time;
                $csv_data[$i][7]  = $val->dp_break_end_time;
                $csv_data[$i][8]  = $val->dp_gaishutu_start_time;
                $csv_data[$i][9]  = $val->dp_gaishutu_end_time;
                $csv_data[$i][10] = $val->dp_ride_flg;
                $csv_data[$i][11] = $val->dp_other_flg;
                $csv_data[$i][12] = $val->dp_memo;
                $csv_data[$i][13] = $val->dp_dakou_address;
                $csv_data[$i][14] = $val->dp_made_by;
                $csv_data[$i][15] = Carbon::parse($val->created_at)->format('Y-m-d H:i:s');
                $csv_data[$i][16] = Carbon::parse($val->updated_at)->format('Y-m-d H:i:s');
                // child table
                $csv_data[$i][17] = null;
                $csv_data[$i][18] = null;
                $csv_data[$i][19] = null;
                $csv_data[$i][20] = null;
                $csv_data[$i][21] = null;
                $csv_data[$i][22] = null;
                $csv_data[$i][23] = null;
                $csv_data[$i][24] = null;
                $csv_data[$i][25] = null;
                $csv_data[$i][26] = null;
                $csv_data[$i][27] = null;
                $csv_data[$i][28] = null;
                $csv_data[$i][29] = null;
                $csv_data[$i][30] = null;

                $j = 0;
                if (!empty($val->dakoku_children)) {
                    foreach ($val->dakoku_children as $child_key => $child) {
                        if ($j != 0) {
                            $csv_data[$i][0]  = null;
                            $csv_data[$i][1]  = null;
                            $csv_data[$i][2]  = null;
                            $csv_data[$i][3]  = null;
                            $csv_data[$i][4]  = null;
                            $csv_data[$i][5]  = null;
                            $csv_data[$i][6]  = null;
                            $csv_data[$i][7]  = null;
                            $csv_data[$i][8]  = null;
                            $csv_data[$i][9]  = null;
                            $csv_data[$i][10] = null;
                            $csv_data[$i][11] = null;
                            $csv_data[$i][12] = null;
                            $csv_data[$i][13] = null;
                            $csv_data[$i][14] = null;
                            $csv_data[$i][15] = null;
                            $csv_data[$i][16] = null;
                        }

                        $csv_data[$i][17] = $child['dp_dakoku_id'];
                        $csv_data[$i][18] = $child['id'];
                        $csv_data[$i][19] = $child['dp_occupation_id'];
                        $csv_data[$i][20] = $child['dp_timezone_id'];
                        $csv_data[$i][21] = $child['dp_support_flg'];
                        $csv_data[$i][22] = $child['dp_support_company_id'];
                        $csv_data[$i][23] = $child['dp_supported_company_id'];
                        $csv_data[$i][24] = $child['dp_nums_of_people'];
                        $csv_data[$i][25] = $child['dp_work_contens_id'];
                        $csv_data[$i][26] = $child['dp_work_location_id'];
                        $csv_data[$i][27] = $child['dp_workers_master'];
                        $csv_data[$i][28] = $child['dp_workers'] && !empty($child['dp_workers']) ? $child['dp_workers'] : null;
                        $csv_data[$i][29] = Carbon::parse($child['created_at'])->format('Y-m-d H:i:s');
                        $csv_data[$i][30] = Carbon::parse($child['updated_at'])->format('Y-m-d H:i:s');

                        $j++;
                        $i++;
                    }
                } else {

                    $i++;
                }
            }

            // 20 Column
            $csv_heading = [
                "ID",
                "日付",
                "ユーザー",
                "打刻区分",
                "出勤時間",
                "退勤時刻",
                "休憩開始時刻",
                "休憩終了時刻",
                "外出開始時刻",
                "外出開終了時刻",
                "運転・同乗",
                "残業",
                "備考",
                "住所",
                "作成者",
                "登録日時",
                "変更日時",
                "Parent ID",
                "Child ID",
                "職種",
                "時間帯",
                "応援区分",
                "応援に行った先",
                "応援来てもらった先",
                "応援人数",
                "作業内容",
                "現場",
                "作業責任者",
                "現場人員",
                "登録日時",
                "変更日時",
            ];
            $excel_heading = ["ID", "日付", "ユーザー", "打刻区分", "出勤時間", "退勤時刻", "休憩開始時刻", "休憩終了時刻", "外出開始時刻", "外出開終了時刻", "運転・同乗", "残業", "備考", "住所", "作成者", "登録日時", "変更日時"];

            if ($type == 'csv') {
                $status = ExcelExport::exportCsv(collect($csv_data), $csv_heading, $path);
            } else if ($type == 'xlsx') {
                $excel_data = $dakou_data->with([
                    'attend_type',
                    'dakoku_children.support_company',
                    'dakoku_children.supported_company',
                    'dakoku_children.occupation',
                    'dakoku_children.work_content',
                    'dakoku_children.work_location',
                    'dakoku_children.timezone',
                ])->whereIn('id', $id_array)->orderBy('target_date', 'asc')->get()->collect()->map(function ($item) {
                    $child = [];
                    if ($item->dakoku_children) {
                        foreach ($item->dakoku_children as $val) {
                            if ($val?->dp_support_flg == 1) {
                                $support_flg = "応援に行った先";
                                $company = $val?->supported_company?->supported_company_name;
                            } else if ($val?->dp_support_flg == 2) {
                                $support_flg = "応援来てもらった先";
                                $company = $val?->support_company?->support_company_name;
                            } else {
                                $support_flg = "なし";
                                $company     = "";
                            }
                            $child[] = [
                                'timezone'    => $val?->timezone?->detail_times,
                                'occp'        => $val?->occupation?->occupation_name,
                                'location'    => $val?->work_location?->location_name,
                                'support_flg' => $support_flg,
                                'company'     => $company,
                                'peoples'     => $val?->dp_nums_of_people
                            ];
                        }
                    }
                    return [
                        'id'         => $item->id,
                        'date'       => Carbon::parse($item->target_date)->format('Y/m/d'),
                        'user'       => User::find($item->dp_user)?->name,
                        'type'       => $item->attend_type->attend_type_name,
                        'start_time' => $item->dp_syukkin_time,
                        'close_time' => $item->dp_taikin_time,
                        'ride_flg'   => $item->dp_ride_flg,
                        'other_flg'  => $item?->attend_status?->attend_name,
                        'memo'       => $item->dp_memo,
                        'child'      => $child
                    ];
                })?->toArray();

                usort($excel_data, function ($a, $b) {
                    $cond = $b['id'] - $a['id'];
                    return $cond;
                });
                // Generated excel by phpspreadsheet unlike csv exporting`

                $spread_sheet = IOFactory::load(base_path('template/master_dakoku.xlsx'));
                $sheet = $spread_sheet->getActiveSheet();

                $row = 0;
                foreach ($excel_data as $item) {
                    $sheet->setCellValue('A' . ($row + 4), $item['id']);
                    $sheet->setCellValue('B' . ($row + 4), $item['date']);
                    $sheet->setCellValue('C' . ($row + 4), $item['user']);
                    $sheet->setCellValue('D' . ($row + 4), $item['type']);
                    $sheet->setCellValue('E' . ($row + 4), $item['start_time']);
                    $sheet->setCellValue('F' . ($row + 4), $item['close_time']);
                    $sheet->setCellValue('G' . ($row + 4), $item['ride_flg']);
                    $sheet->setCellValue('H' . ($row + 4), $item['memo']);

                    $sheet->getStyle('C' . ($row + 4))->getAlignment()->setWrapText(true);
                    $sheet->getStyle('H' . ($row + 4))->getAlignment()->setWrapText(true);


                    if (count($item['child']) > 1) {
                        // merge cells
                        for ($i = 1; $i <= 8; $i++) {
                            $sheet->mergeCellsByColumnAndRow($i, $row + 4, $i, $row + count($item['child']) + 3);
                            $sheet->getStyleByColumnAndRow($i, $row + 4)->getAlignment()->setWrapText(true);
                        }
                    }

                    foreach ($item['child'] as $val) {
                        $sheet->setCellValue('I' . ($row + 4), $val['timezone']);
                        $sheet->setCellValue('J' . ($row + 4), $val['occp']);
                        $sheet->setCellValue('K' . ($row + 4), $val['location']);
                        $sheet->setCellValue('L' . ($row + 4), $val['support_flg']);
                        $sheet->setCellValue('M' . ($row + 4), $val['company']);
                        $sheet->setCellValue('N' . ($row + 4), $val['peoples']);

                        $sheet->getStyle('J' . ($row + 4))->getAlignment()->setWrapText(true);
                        $sheet->getStyle('K' . ($row + 4))->getAlignment()->setWrapText(true);
                        $sheet->getStyle('M' . ($row + 4))->getAlignment()->setWrapText(true);
                        $sheet->getRowDimension($row+4)->setRowHeight(-1);
                        $row++;
                    }
                }

                $sheet_style = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => '000000']
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ];
                $sheet->getStyle('A4:N'. $row+3)->applyFromArray($sheet_style);

                $new_excel = new Xlsx($spread_sheet);
                $new_excel->save(public_path('storage/' . $path));
                $status = true;
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
            Excel::import(new AttendDataImport, $csv);
            Cache::flush();
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
