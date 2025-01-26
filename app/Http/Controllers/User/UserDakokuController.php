<?php

namespace App\Http\Controllers\User;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\DakokuRequest;
use App\Http\Requests\SyukinRequest;
use App\Http\Requests\TaikinRequest;
use App\Http\Requests\UserDakokuRequest;
use Illuminate\Http\Request;
use App\Models\{WorkLocation, WorkContent, User, SupportedCompany, Occupation, DakouData, AttendStatus, AttendType, CompanySetting, DakouChild, Holiday, SupportCompany, TimeZone};
use App\Service\DateService;
use Carbon\Carbon;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UserDakokuController extends Controller
{
    public $dateService;
    public function __construct(DateService $dateService)
    {
        $this->dateService = $dateService;
    }
    public function index(Request $request)
    {
        $start_date = $request->s;
        $end_date   = $request->e;

        $data_for_count = DakouData::where('dp_user', auth()->user()->id)
            ->when($start_date, fn($query) => $query->whereDate('target_date', '>=', $start_date))
            ->when($end_date, fn($query) => $query->whereDate('target_date', '<=', $end_date))->orderBy('target_date')->get();
        if (!empty($data_for_count)) {
            $first_date = $data_for_count[0]['target_date'];
            $last_date = $data_for_count[count($data_for_count) - 1]['target_date'];
        }

        $allData = new Collection();

        $blank_start_date = Carbon::parse($first_date);
        $blank_last_date = Carbon::parse($last_date);
        while ($blank_start_date <= $blank_last_date) {
            $allData->push($blank_start_date->format('Y-m-d'));
            $blank_start_date->addDay();
        }

        $actualData  = DakouData::with([
            'user.user_data.break_times.organization',
            'attend_type',
            'dakoku_children',
            'user',
            'dakoku_children.timezone',
            'dakoku_children.support_company',
            'dakoku_children.supported_company',
            'attend_status',
            'dakoku_children.occupation',
            'dakoku_children.work_content',
            'dakoku_children.work_location',
        ])
            ->where('dp_user', auth()->user()->id)
            ->where(function ($query) use ($start_date, $end_date) {
                if ($start_date) {
                    $query->whereDate('target_date', '>=', $start_date);
                }
                if ($end_date) {
                    $query->whereDate('target_date', '<=', $end_date);
                }
            })
            ->orderBy('target_date', 'DESC')
            ->get()
            ->keyBy('target_date');

        $filledData = $allData->map(function ($date) use ($actualData) {
            if (isset($actualData[$date])) {
                return $actualData[$date];
            } else {
                return [
                    'id'              => null,
                    'target_date'     => $date,
                    'dakoku_children' => [],
                    'created_at'      => null,
                    'updated_at'      => null,
                    'attend_status'   => null,
                ];
            }
        });

        $sortedData = $filledData->sortByDesc('target_date');
        $sortedData = array_values($sortedData->toArray());
        $perPage = 50;
        $page = request('page', 1);
        $dakouData = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($sortedData, ($page-1) * $perPage, $perPage),
            count($sortedData),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $close_status = CompanySetting::first()?->company_month_closing_status;
        $close_month  = CompanySetting::first()?->company_month_closing_date;
        $min_date = $this->dateService->min_date($close_status, $close_month);
        return Inertia::render('User/DakokuData/UserDakokuList', compact('dakouData', 'start_date', 'end_date', 'min_date'));
    }
    public function syukkin()
    {
        $userOrganization  = User::with('user_data.break_times.organization')
            ->where('id', auth()->user()->id)
            ->first();
        $attend_type         = AttendType::all();
        $support_company     = SupportCompany::all();
        $supported_company   = SupportedCompany::all();
        $attend_status       = AttendStatus::all();
        $occupation          = Occupation::all();
        $work_contents       = WorkContent::all();
        $work_locations      = WorkLocation::where("location_flag", 1)->get();
        $timezones           = TimeZone::all();
        $users               = User::where('role', '!=', 1)->get();
        $today_attend_status = DakouData::where('dp_user', auth()->user()->id)->whereDate('target_date', now())->exists();

        $dakoku_data = DakouData::with([
            'user.user_data.break_times.organization',
            'attend_type',
            'dakoku_children',
            'user',
            'dakoku_children.support_company',
            'dakoku_children.supported_company',
            'attend_status',
            'dakoku_children.occupation',
            'dakoku_children.work_content',
            'dakoku_children.work_location',
        ])->where(function ($q) {
            $q->whereDate('target_date', date('Y-m-d'));
            $q->where('dp_user', auth()->user()->id);
        })->first();
        $userOrganization  = User::with('user_data.break_times.organization')
            ->where('id', auth()->user()->id)
            ->first();

        return Inertia::render("User/TodayDakoku/UserSyukkin", compact(
            'dakoku_data',
            'attend_type',
            'support_company',
            'supported_company',
            'attend_status',
            'occupation',
            'work_contents',
            'work_locations',
            'timezones',
            'users',
            'userOrganization',
            'today_attend_status',
        ));
    }
    public function syukkinStore(SyukinRequest $request)
    {
        // 初めて出勤登録時には職種、時間帯を空欄にして登録が可能ですが、いったん編集中または退勤の際には必須
        $exist = DakouData::where('dp_user', auth()->user()->id)->where('target_date', date('Y-m-d'))->exists();
        $children = $request->input('children');

        foreach ($children as $key => $item) {
            if ($item['supportFlag'] == 1) {
                $request->validate(
                    ["children.{$key}.supportedCompany" => 'required'],
                    ["children.{$key}.supportedCompany.required" => '応援に行った先を選択してください。']
                );
            } else if ($item['supportFlag'] == 2) {
                $request->validate(
                    ["children.{$key}.supportCompany" => 'required'],
                    ["children.{$key}.supportCompany.required" => '応援来てもらった先を選択してください。']
                );
            }
        }
        $this->user_attend_data_store($request);
    }

    public function user_attend_data_store($request)
    {
        DB::transaction(function () use ($request) {
            $id       = $request->id;
            $user_id  = $request->dpUser;
            $children = $request->children;
            if (!$id && !$user_id) {
                // 本日出勤登録をしていない場合
                $parent = DakouData::updateOrCreate(
                    [
                        'target_date'      => date('Y-m-d'),
                        'dp_user'          => auth()->user()->id,
                    ],
                    [
                        'dp_status'        => 1,
                        'dp_type'          => $request->dpType,
                        'dp_syukkin_time'  => $request->syukkinTime,
                        'dp_ride_flg'      => $request->driveRide,
                        'dp_other_flg'     => $request->otherFlag,
                        'dp_memo'          => $request->memo,
                        'dp_dakou_address' => $request->address,
                        'dp_made_by'       => auth()->user()->id,
                    ]
                );
                if ($parent->id && $parent->dp_user == auth()->user()->id) {
                    foreach ($request->children as $item) {
                        DakouChild::create(
                            [
                                'dp_dakoku_id'            => $parent->id,
                                'dp_occupation_id'        => $item['occupation'],
                                'dp_unique_counter'       => $item['uniqueCounter'],
                                'dp_work_contens_id'      => $item['workContent'],
                                'dp_work_location_id'     => $item['location'],
                                'dp_support_flg'          => $item['supportFlag'],
                                'dp_support_company_id'   => $item['supportFlag'] == 2 ? $item['supportCompany'] : null,
                                'dp_supported_company_id' => $item['supportFlag'] == 1 ? $item['supportedCompany'] : null,
                                'dp_nums_of_people'       => $item['supportFlag'] != 0 ? $item['peopleNums'] : null,
                                'dp_timezone_id'          => $item['timezone']
                            ]
                        );
                    }
                }
            } else {
                // 出勤後に打刻情報を追加、編集する場合
                DakouData::where('id', $request->id)->where('dp_user', auth()->user()->id)->update([
                    'dp_type'          => $request->dpType,
                    'dp_ride_flg'      => $request->driveRide,
                    'dp_other_flg'     => $request->otherFlag,
                    'dp_memo'          => $request->memo,
                ]);

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
                    DakouChild::with('dakoku')->whereIn('id', $id_arr_to_remove)->whereHas('dakoku', function ($query) {
                        $query->where('dp_user', auth()->user()->id);
                    })->delete();

                    // step 2: add new children
                    foreach ($children as $item) {
                        // when is $item['id'] == null, this case mean added new child
                        // so that, in this case have to add new child dakoku data
                        // however, $child_id_arr are not include $item['id], this means remove the child dakoku data
                        DakouChild::updateOrCreate(
                            ['id' => $item['id']],
                            [
                                'dp_dakoku_id'            => $id,
                                'dp_support_flg'          => $item['supportFlag'],
                                'dp_support_company_id'   => $item['supportFlag'] == 2 ? $item['supportCompany'] : null,
                                'dp_supported_company_id' => $item['supportFlag'] == 1 ? $item['supportedCompany'] : null,
                                'dp_nums_of_people'       => $item['supportFlag'] != 0 ? $item['peopleNums'] : null,
                                'dp_occupation_id'        => $item['occupation'],
                                'dp_unique_counter'       => $item['uniqueCounter'],
                                'dp_work_contens_id'      => $item['workContent'],
                                'dp_work_location_id'     => $item['location'],
                                'dp_timezone_id'          => $item['timezone'],
                            ]
                        );
                    }
                }
            }
            Cache::flush();
        });
    }
    public function taikinStore(TaikinRequest $request)
    {
        $children = $request->input('children');

        foreach ($children as $key => $item) {
            if ($item['supportFlag'] == 1) {
                $request->validate(
                    ["children.{$key}.supportedCompany" => 'required'],
                    ["children.{$key}.supportedCompany.required" => '応援に行った先を選択してください。']
                );
            } else if ($item['supportFlag'] == 2) {
                $request->validate(
                    ["children.{$key}.supportCompany" => 'required'],
                    ["children.{$key}.supportCompany.required" => '応援来てもらった先を選択してください。']
                );
            }
        }
        DB::transaction(function () use ($request) {
            $this->user_attend_data_store($request);
            DakouData::where('id', $request->id)
                ->where('dp_user', auth()->user()->id)
                ->update([
                    'dp_taikin_time' => $request->taikinTime,
                    'dp_status'      => 2,
                ]);
        });
        return redirect()->route('user.attendance.today.complete');
    }

    public function dailyComplete()
    {
        $dakou_status  = DakouData::where('target_date', date('Y-m-d'))
            ->where('dp_user', auth()->user()->id)
            ->first();
        return Inertia::render('User/TodayDakoku/CompleteWorking', compact('dakou_status'));
    }

    public function create(Request $request, $date=null, $id = null)
    {
        $edit_status  = $request->input('edit');
        $close_status = CompanySetting::first()?->company_month_closing_status;
        $close_date   = CompanySetting::first()?->company_month_closing_date;
        if ($edit_status) {
            $dakoku_date = DakouData::where('id', $id)->first()?->target_date;
            if ($close_status) {
                if (date('Y-m', strtotime($dakoku_date)) <= date('Y-m', strtotime($close_date))) {
                    return redirect()->route('user.attendance.list.index');
                }
            }
        }
        $choosingDate = $request->date ?? date('Y-m-d');

        $dakoku_data = DakouData::with([
            'user.user_data.break_times.organization',
            'attend_type',
            'dakoku_children',
            'user',
            'dakoku_children.support_company',
            'dakoku_children.supported_company',
            'attend_status',
            'dakoku_children.occupation',
            'dakoku_children.work_content',
            'dakoku_children.work_location',
        ])->where(function ($q) use ($choosingDate, $id) {
            if ($id) {
                $q->where('id', $id);
            } else if ($choosingDate) {
                $q->whereDate('target_date', $choosingDate);
                $q->where('dp_user', auth()->user()->id);
            }
        })->first();

        // Creating mode
        if (!$id) {
            $dakoku_data = null;
        }
        $userOrganization  = User::with('user_data.break_times.organization')
            ->where('id', auth()->user()->id)
            ->first();
        $attend_type       = AttendType::all();
        $support_company   = SupportCompany::all();
        $supported_company = SupportedCompany::all();
        $attend_status     = AttendStatus::all();
        $occupation        = Occupation::all();
        $work_contents     = WorkContent::all();
        $work_locations    = WorkLocation::where("location_flag", 1)->get();
        $timezones         = TimeZone::all();
        $users             = User::where('role', '!=', 1)->get();
        $holidays_         = Holiday::where('holiday_flag', 1)->where('paid_holiday', 0)->whereYear('holiday_date', date("Y"))->whereMonth('holiday_date', date('m'))->pluck('holiday_date');
        $holidays          = $holidays_->map(function ($item) {
            return [
                'date' => Carbon::parse($item),
                'type' => 'dot',
                'tooltip' => [['text' => '休日', 'color' => 'green']]
            ];
        });
        /**
         * 制限は、５営業日（休日を除いて５日）です。 
         * 例えば今日（４月２５日）の５営業日前は、土日を休みとすると、４月１８日になります。
         * ４月１８日より前の打刻が入力できない。
         * という認識です。
         */
        $min_date = $this->dateService->min_date($close_status, $close_date);
        return Inertia::render("User/DakokuData/CreateDakoku", compact(
            'dakoku_data',
            'attend_type',
            'support_company',
            'supported_company',
            'attend_status',
            'occupation',
            'work_contents',
            'work_locations',
            'timezones',
            'users',
            'userOrganization',
            'choosingDate',
            'close_status',
            'close_date',
            'min_date',
            'holidays'
        ));
    }
    public function store(UserDakokuRequest $request)
    {
        // 締め処理
        $t_date       = $request->targetDate;
        $close_status = CompanySetting::first()?->company_month_closing_status;
        $close_date   = CompanySetting::first()?->company_month_closing_date;
        $c_year       = date('Y', strtotime($close_date));
        $c_month      = date('m', strtotime($close_date));
        $t_year       = date('Y', strtotime($t_date));
        $t_month      = date('m', strtotime($t_date));


        if ($close_status && $c_year == $t_year && $c_month == $t_month) {
            return redirect()->back()->withErrors(['error' => '月締め処理されたので追加、編集、削除できません。']);
        }

        // 日中重複登録をする場合
        $duplicate_exists = DakouData::whereDate('target_date', $t_date)->where('dp_user', auth()->user()->id)->exists();
        if ($duplicate_exists && !$request->input('id')) {
            return redirect()->back()->withErrors(['error' => sprintf('%sの打刻データはすでに登録されています。', Carbon::parse($t_date)->format('Y年n月j日'))]);
        }
        // 出勤登録をせずに退勤登録をする場合
        // $syukin_check = DakouData::where('dp_user', auth()->user()->id)->whereDate('target_date', $t_date)->exists();
        // if (!$syukin_check && $request->attendType == 2) {
        //     return redirect()->back()->withErrors(['error' => '出勤登録をしていません。まず出勤登録をしてください。']);
        // }

        $children = $request->children;
        foreach ($children as $key => $item) {
            if ($item['supportFlag'] == 1) {
                $request->validate(
                    ["children.{$key}.supportedCompany" => 'required'],
                    ["children.{$key}.supportedCompany.required" => '応援に行った先を選択してください。']
                );
            } else if ($item['supportFlag'] == 2) {
                $request->validate(
                    ["children.{$key}.supportCompany" => 'required'],
                    ["children.{$key}.supportCompany.required" => '応援来てもらった先を選択してください。']
                );
            }
        }
        DB::transaction(function () use ($t_date, $request) {
            $id       = $request->id;
            $user_id  = $request->dpUser;
            $children = $request->children;

            $attend_data = [
                'target_date'      => $t_date,
                'dp_user'          => auth()->user()->id,
                'dp_syukkin_time'  => $request->syukkinTime,
                'dp_taikin_time'   => $request->taikinTime,
                'dp_status'        => $request->attendType,
                'dp_type'          => $request->dpType,
                'dp_ride_flg'      => $request->driveRide,
                'dp_other_flg'     => $request->otherFlag,
                'dp_memo'          => $request->memo,
                'dp_dakou_address' => $request->address,
                'dp_made_by'       => auth()->user()->id,
            ];
            if ($request->attendType == 1) {
                $attend_data['dp_taikin_time']  = null;
            }

            $old_data = DakouData::find($id);
            if (!$old_data || !$user_id) {
                // Creating
                $parent = DakouData::create($attend_data);
                if ($parent->id && $parent->dp_user == auth()->user()->id) {
                    foreach ($request->children as $item) {
                        DakouChild::create([
                            'dp_dakoku_id'            => $parent->id,
                            'dp_occupation_id'        => $item['occupation'],
                            'dp_unique_counter'       => $item['uniqueCounter'],
                            'dp_work_contens_id'      => $item['workContent'],
                            'dp_work_location_id'     => $item['location'],
                            'dp_support_flg'          => $item['supportFlag'],
                            'dp_support_company_id'   => $item['supportFlag'] == 2 ? $item['supportCompany'] : null,
                            'dp_supported_company_id' => $item['supportFlag'] == 1 ? $item['supportedCompany'] : null,
                            'dp_nums_of_people'       => $item['supportFlag'] != 0 ? $item['peopleNums'] : null,
                            'dp_timezone_id'          => $item['timezone']
                        ]);
                    }
                }
            } else {
                // Modifing
                DakouData::where('id', $id)->update($attend_data);
                if ($user_id == auth()->user()->id) {
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
                        DakouChild::with('dakoku')->whereIn('id', $id_arr_to_remove)->whereHas('dakoku', function ($query) {
                            $query->where('dp_user', auth()->user()->id);
                        })->delete();
                        // step 2: add new children
                        foreach ($children as $item) {
                            // when is $item['id'] == null, this case mean added new child
                            // so that, in this case have to add new child dakoku data
                            // however, $child_id_arr are not include $item['id], this means remove the child dakoku data
                            DakouChild::updateOrCreate(
                                [
                                    'id' => $item['id'],
                                ],
                                [
                                    'dp_dakoku_id'            => $id,
                                    'dp_support_flg'          => $item['supportFlag'],
                                    'dp_support_company_id'   => $item['supportFlag'] == 2 ? $item['supportCompany'] : null,
                                    'dp_supported_company_id' => $item['supportFlag'] == 1 ? $item['supportedCompany'] : null,
                                    'dp_nums_of_people'       => $item['supportFlag'] != 0 ? $item['peopleNums'] : null,
                                    'dp_occupation_id'        => $item['occupation'],
                                    'dp_unique_counter'       => $item['uniqueCounter'],
                                    'dp_work_contens_id'      => $item['workContent'],
                                    'dp_work_location_id'     => $item['location'],
                                    'dp_timezone_id'          => $item['timezone'],
                                ]
                            );
                        }
                    }
                }
            }
        });
        Cache::flush();
        return redirect()->route('user.attendance.list.index');
    }
    public function detail(Request $request)
    {
        $users = User::users();
        $dakokuDetail = DakouData::with([
            'author',
            'attend_type',
            'attend_status',
            'dakoku_children',
            'dakoku_children.support_company',
            'dakoku_children.supported_company',
            'dakoku_children.occupation',
            'dakoku_children.work_location',
            'dakoku_children.work_content',
            'dakoku_children.worker_master',
            'dakoku_children.timezone',
        ])->where('id', $request->id)->first();
        return Inertia::render('User/DakokuData/UserDakokuDetail', compact('dakokuDetail', 'users'));
    }
    public function destroy(Request $request)
    {
        DakouData::where('id', $request->id)->delete();
    }
}
