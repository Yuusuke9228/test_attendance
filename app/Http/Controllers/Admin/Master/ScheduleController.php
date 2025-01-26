<?php

namespace App\Http\Controllers\Admin\Master;

use App\Helper\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CsvFileRequest;
use App\Http\Requests\ScheduleRequest;
use App\Imports\ScheduleCsvImport;
use App\Models\Occupation;
use App\Models\Schedule;
use App\Models\User;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $key   = $request->input("key");
        $id   = $request->input("id");
        $sdate = $request->input("sdate");
        $cdate = $request->input("cdate");
        $occp  = $request->input("occp");
        $loc   = $request->input("loc");
        $user  = $request->input("user");
        $start = $request->input("start");
        $close = $request->input("close");

        $user_id_for_schedule = User::where('name', 'like', "%" . $key . "%")
            ->orWhere('code', 'like', "%" . $key . "%")
            ->orWhere('email', 'like', "%" . $key . "%")
            ->pluck('id');
        $schedule = Schedule::with(['occupations', 'locations'])
            ->when($key, function ($q) use ($key, $user_id_for_schedule) {
                foreach ($user_id_for_schedule as $user_id) {
                    $q->orWhereJsonContains('user_id', (int)$user_id);
                }
                $q->orWhereHas('occupations', function ($sq) use ($key) {
                    $sq->where('occupation_name', 'like', "%" . $key . "%");
                });
                $q->orWhereHas('locations', function ($sq) use ($key) {
                    $sq->where('location_name', 'like', "%" . $key . "%");
                });
            })->when($id, function ($q) use ($id) {
                $q->where('id', $id);
            })->when($sdate, function ($q) use ($sdate) {
                $q->whereDate('schedule_date', ">=", $sdate);
            })->when($cdate, function ($q) use ($cdate) {
                $q->whereDate('schedule_date', "<=", $cdate);
            })->when($cdate, function ($q) use ($start) {
                $q->whereTime('schedule_start_time', "<=", $start);
            })->when($cdate, function ($q) use ($close) {
                $q->whereTime('schedule_end_time', "<=", $close);
            })->when($occp, function ($q) use ($occp) {
                $q->where('occupation_id',  $occp);
            })->when($loc, function ($q) use ($loc) {
                $q->where('location_id', $loc);
            })->when($user, function ($q) use ($user) {
                $q->whereJsonContains('user_id', (int)$user);
            })->paginate(100);
        $usersList = User::users();
        $occupations = Occupation::all();
        $locations = WorkLocation::where("location_flag", 1)->get();
        return Inertia::render('Admin/Master/Schedule/ScheduleIndex', compact('schedule', 'usersList', 'occupations', 'locations'));
    }

    public function create()
    {
        $occupationList = Occupation::all();
        $workLocationList = WorkLocation::where("location_flag", 1)->get();
        $usersList = User::users();
        return Inertia::render('Admin/Master/Schedule/CreateSchedule', compact('occupationList', 'workLocationList', 'usersList'));
    }

    public function store(ScheduleRequest $request)
    {
        if ($request) {
            $redirect = $request->redirectOption;
            $schedule = Schedule::create([
                'user_id' => json_encode($request->users, JSON_NUMERIC_CHECK),
                'location_id' => $request->workLocation['id'],
                'schedule_date' => $request->date,
                'schedule_start_time' => $request->startTime,
                'schedule_end_time' => $request->endTime,
                'occupation_id' => $request->occupation['id'],
            ]);
            if ($redirect == 1) {
                return redirect()->route('admin.master.schedule.index');
            } else if ($redirect == 2) {
                return redirect()->route('admin.master.schedule.show', ['id' => $schedule->id]);
            }
        }
    }
    public function show(Request $request, $id)
    {
        $detailSchedule = Schedule::with(['occupations', 'locations'])->find($id);
        $usersList = User::users();
        return Inertia::render('Admin/Master/Schedule/ScheduleDetail', compact('detailSchedule', 'usersList'));
    }
    public function edit(Request $request, $id)
    {
        $occupationList = Occupation::all();
        $workLocationList = WorkLocation::where("location_flag", 1)->get();
        $usersList = User::users();
        $editSchedule = Schedule::with(['occupations', 'locations'])->find($id);
        return Inertia::render('Admin/Master/Schedule/ScheduleEdit', compact('editSchedule', 'occupationList', 'workLocationList', 'usersList'));
    }
    public function update(ScheduleRequest $request)
    {
        $redirect = $request->redirectOption;
        $schedule = Schedule::where('id', $request->id)->update([
            'user_id' => json_encode($request->users, JSON_NUMERIC_CHECK),
            'location_id' => $request->workLocation['id'],
            'schedule_date' => $request->date,
            'schedule_start_time' => $request->startTime,
            'schedule_end_time' => $request->endTime,
            'occupation_id' => $request->occupation['id'],
        ]);
        if ($redirect == 1) {
            return redirect()->route('admin.master.schedule.index');
        } else if ($redirect == 2) {
            return redirect()->route('admin.master.schedule.show', ['id' => $schedule->id]);
        } else {
            return redirect()->route('admin.master.schedule.create');
        }
    }
    public function destroy(Request $request)
    {
        if ($request->id) {
            Schedule::where('id', $request->id)->delete();
            return redirect()->route('admin.master.schedule.index');
        }
    }
    public function export(Request $request)
    {
        try {
            $type             = $request->input('type');
            $file_name_format = "作業予定管理_%d.%s";
            $file_name        = sprintf($file_name_format, date('YmdHis'), $type);
            $path             = 'master/' . $file_name;
            $schedule_data    = Schedule::query();

            $csv_data   = $schedule_data->get();
            $excel_data = $schedule_data->with(['occupations', 'locations'])->get()->collect()->map(function ($item) {
                $users      = User::query()->whereIn('id', json_decode($item->user_id))->get();
                $user_names = "";
                foreach ($users as $user) {
                    $user_names .= $user->name . "、";
                }
                $user_names = mb_substr($user_names, 0, -1);
                return [
                    'id'                  => $item->id,
                    'user_ids'            => $user_names,
                    'location'            => $item->locations->location_name,
                    'schedule_date'       => Carbon::parse($item->schedule_date)->format('Y-m-d'),
                    'schedule_start_time' => $item->schedule_start_time,
                    'schedule_end_time'   => $item->schedule_end_time,
                    'occupation'          => $item->occupations->occupation_name,
                    'created_at'          => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
                    'updated_at'          => Carbon::parse($item->updated_at)->format('Y-m-d H:i:s'),
                ];
            });
            $heading = ["ID",  "従業員", "現場", "日付",  "開始予定時刻", "終了予定時刻", "職種",  "作成日時", "更新日時"];
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
            Excel::import(new ScheduleCsvImport, $csv);
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
