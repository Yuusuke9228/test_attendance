<?php

namespace App\Http\Controllers\Admin\Master;

use App\Helper\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendStatusRequest;
use App\Http\Requests\CsvFileRequest;
use App\Imports\AttendStatusImport;
use App\Models\AttendStatus;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class AttendStatusController extends Controller
{
    public function index(Request $request)
    {
        $key          = $request->input("key");
        $id           = $request->input("id");
        $name         = $request->input("name");
        $attendStatus = AttendStatus::when($key, function($query) use ($key) {
            $query->where("attend_name", "like","%".$key."%");
        })->when($id, function ($q) use ($id) {
            $q->where("id", $id);
        })->when($name, function ($q) use ($name) {
            $q->where("attend_name", "like", "%". $name ."%");
        })->paginate(100);
        return Inertia::render('Admin/Master/AttendStatus/AttendStatusIndex', compact('attendStatus'));
    }
    public function create()
    {
        return Inertia::render('Admin/Master/AttendStatus/CreateAttendStatus');
    }
    public function store(AttendStatusRequest $request)
    {
        if ($request) {
            $status = AttendStatus::create([
                'attend_name'  => $request->statusName,
            ]);

            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.attend_statuses.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.attend_statuses.show', ['id' => $status->id]);
            } else {
                return redirect()->route('admin.master.attend_statuses.create');
            }
        }
    }
    public function show($id)
    {
        $attendStatus = AttendStatus::find($id);
        return Inertia::render('Admin/Master/AttendStatus/AttendStatusDetail', compact('attendStatus'));
    }
    public function edit($id)
    {
        $attendStatus = AttendStatus::find($id);
        return Inertia::render('Admin/Master/AttendStatus/AttendStatusEdit', compact('attendStatus'));
    }
    public function update(AttendStatusRequest $request)
    {
        if ($request) {
            AttendStatus::where('id', $request->id)->update([
                'attend_name' => $request->statusName
            ]);

            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.attend_statuses.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.attend_statuses.show', ['id' => $request->id]);
            } else {
                return redirect()->route('admin.master.attend_statuses.create');
            }
        }
    }
    public function destroy(Request $request)
    {
        if ($request) {
            AttendStatus::where('id', $request->id)->delete();
            return redirect()->route('admin.master.attend_statuses.index');
        }
    }
    public function export(Request $request)
    {
        try {
            $type               = $request->input('type');
            $file_name_format   = "残業・早退・遅刻 _%d.%s";
            $file_name          = sprintf($file_name_format, date('YmdHis'), $type);
            $path               = 'master/' . $file_name;
            $attend_status_data = AttendStatus::query();

            $csv_data   = $attend_status_data->get();
            $excel_data = $attend_status_data->get()->collect()->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'attend_name' => $item->attend_name,
                    'created_at'  => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
                    'updated_at'  => Carbon::parse($item->updated_at)->format('Y-m-d H:i:s'),
                ];
            });
            $heading = ["ID", "選択肢表示名", "作成日時", "更新日時"];
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
            Excel::import(new AttendStatusImport, $csv);
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
