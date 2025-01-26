<?php

namespace App\Http\Controllers\Admin\Master;

use App\Helper\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\BreakTimeRequest;
use App\Http\Requests\CsvFileRequest;
use App\Imports\BreakTimeImport;
use App\Models\BreakTime;
use App\Models\Organization;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BreakTimeManagementController extends Controller
{
    public function index(Request $request)
    {
        $key        = $request->input("key");
        $id         = $request->input("id");
        $code       = $request->input("code");
        $org        = $request->input("org");
        $mgn        = $request->input("mgn");
        $breakTimes = BreakTime::with('organization')->when($key, function ($query) use ($key) {
            return $query->orWhere('break_name', 'LIKE', '%' . $key . '%')
                ->orWhere('break_work_pattern_cd', 'LIKE', '%' . $key . '%')
                ->orWhereHas('organization', function ($sq) use ($key) {
                    $sq->where('organization_name', 'LIKE', '%' . $key . '%');
                });
        })->when($id, function($q) use ($id) {
            $q->where('id', $id);
        })->when($code, function($q) use ($code) {
            $q->where('break_work_pattern_cd', "like", "%". $code . "%");
        })->when($mgn, function($q) use ($mgn) {
            $q->where('break_name', "like", "%". $mgn . "%");
        })->when($org, function($q) use ($org) {
            $q->whereRelation('organization', 'organization_name', 'like', "%".$org."%");
        })->paginate(100);
        return Inertia::render('Admin/Master/BreakTime/BreakTimeIndex', compact('breakTimes'));
    }
    public function create()
    {
        $organization = Organization::all();
        return Inertia::render('Admin/Master/BreakTime/CreateBreakTime', compact('organization'));
    }
    public function store(BreakTimeRequest $request)
    {
        if ($request) {
            $breakTime =  BreakTime::create([
                'break_work_pattern_cd'     => $request->code,
                'break_start_time'          => $request->startTime,
                'break_end_time'            => $request->endTime,
                'break_organization'        => $request->organization,
                'break_name'                => $request->breakName,
                'break_start_time1'         => $request->startTime_1,
                'break_end_time1'           => $request->endTime_1,
                'break_start_time2'         => $request->startTime_2,
                'break_end_time2'           => $request->endTime_2,
                'break_start_time3'         => $request->startTime_3,
                'break_end_time3'           => $request->endTime_3,
            ]);
            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.breaktime.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.breaktime.show', ['id' => $breakTime->id]);
            } else {
                return redirect()->route('admin.master.breaktime.create');
            }
        }
    }
    public function show($id)
    {
        $breakTimeDetail = BreakTime::with('organization')->find($id);
        return Inertia::render('Admin/Master/BreakTime/BreakTimeDetail', compact('breakTimeDetail'));
    }
    public function edit($id)
    {
        $organization = Organization::all();
        $breakTimeDetail = BreakTime::find($id);
        return Inertia::render('Admin/Master/BreakTime/BreakTimeEdit', compact('breakTimeDetail', 'organization'));
    }
    public function update(BreakTimeRequest $request)
    {
        if ($request) {
            BreakTime::where('id', $request->id)->update([
                'break_work_pattern_cd'     => $request->code,
                'break_start_time'          => $request->startTime,
                'break_end_time'            => $request->endTime,
                'break_organization'        => $request->organization,
                'break_name'                => $request->breakName,
                'break_start_time1'         => $request->startTime_1,
                'break_end_time1'           => $request->endTime_1,
                'break_start_time2'         => $request->startTime_2,
                'break_end_time2'           => $request->endTime_2,
                'break_start_time3'         => $request->startTime_3,
                'break_end_time3'           => $request->endTime_3,
            ]);
            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.breaktime.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.breaktime.show', ['id' => $request->id]);
            } else {
                return redirect()->route('admin.master.breaktime.create');
            }
        }
    }
    public function destroy(Request $request)
    {
        if ($request) {
            BreakTime::where('id', $request->id)->delete();
            return redirect()->route('admin.master.breaktime.index');
        }
    }
    public function export(Request $request)
    {
        try {
            $type             = $request->input('type');
            $file_name_format = "休憩時間・勤務形態管理_%d.%s";
            $file_name        = sprintf($file_name_format, date('YmdHis'), $type);
            $path             = 'master/' . $file_name;
            $break_times_data    = BreakTime::query();

            $csv_data   = $break_times_data->get();
            $excel_data = $break_times_data->get()->collect()->map(function ($item) {
                return [
                    'id'                    => $item->id,
                    'break_work_pattern_cd' => $item->break_work_pattern_cd,
                    'break_start_time'      => $item->break_start_time,
                    'break_end_time'        => $item->break_end_time,
                    'organization'          => $item->organization->organization_name,
                    'break_name'            => $item->break_name,
                    'break_start_time1'     => $item->break_start_time1,
                    'break_end_time1'       => $item->break_end_time1,
                    'break_start_time2'     => $item->break_start_time2,
                    'break_end_time2'       => $item->break_end_time2,
                    'break_start_time3'     => $item->break_start_time3,
                    'break_end_time3'       => $item->break_end_time3,
                    'created_at'            => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
                    'updated_at'            => Carbon::parse($item->updated_at)->format('Y-m-d H:i:s'),
                ];
            });
            $heading = ["ID", "勤務形態コード", "組織", "管理名", "勤務開始時刻", "勤務終了時刻", "休憩開始時刻１", "休憩終了時刻１", "休憩開始時刻２", "休憩終了時刻２", "休憩開始時刻３", "休憩終了時刻３", "作成日時", "更新日時"];
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
            Excel::import(new BreakTimeImport, $csv);
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
