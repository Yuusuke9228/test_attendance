<?php

namespace App\Http\Controllers\Admin\Master;

use App\Helper\ExcelExport;
use App\Helper\ExcelImport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CsvFileRequest;
use App\Http\Requests\TimeZoneReuqest;
use App\Imports\CsvImport\TimezoneImport;
use Illuminate\Http\Request;
use App\Models\TimeZone;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class TimeZoneController extends Controller
{
    public function index(Request $request)
    {
        $key      = $request->input('key');
        $id       = $request->input('id');
        $zone     = $request->input('zone');
        $timeZone = TimeZone::when($key, function ($query) use ($key) {
            return $query->where('detail_times', "like", "%" . $key . "%");
        })->when($id, function ($query) use ($id) {
            return $query->where('id', $id);
        })->when($zone, function ($query) use ($zone) {
            return $query->where('detail_times', "like", "%" . $zone . "%");
        })->paginate(100);
        return Inertia::render('Admin/Master/TimeZone/TimeZoneIndex', compact('timeZone', 'key'));
    }
    public function create()
    {
        return Inertia::render('Admin/Master/TimeZone/CreateTimeZone');
    }
    public function store(TimeZoneReuqest $request)
    {
        if ($request) {
            $timezone =  TimeZone::create([
                'detail_times'  => $request->timezone,
            ]);
            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.timezone.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.timezone.show', ['id' => $timezone->id]);
            } else {
                return redirect()->route('admin.master.timezone.create');
            }
        }
    }
    public function show($id)
    {
        $timezone = TimeZone::find($id);
        return Inertia::render('Admin/Master/TimeZone/TimeZoneDetail', compact('timezone'));
    }
    public function edit($id)
    {
        $timezoneData = TimeZone::find($id);
        return Inertia::render('Admin/Master/TimeZone/TimeZoneEdit', compact('timezoneData'));
    }
    public function update(TimeZoneReuqest $request)
    {
        if ($request) {
            TimeZone::where('id', $request->id)->update(
                [
                    'detail_times'  => $request->timezone,
                ]
            );
            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.timezone.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.timezone.show', ['id' => $request->id]);
            } else {
                return redirect()->route('admin.master.timezone.create');
            }
        }
    }
    public function destroy(Request $request)
    {
        if ($request) {
            TimeZone::where('id', $request->id)->delete();
            return redirect()->route('admin.master.timezone.index');
        }
    }
    public function export(Request $request)
    {
        try {
            $type             = $request->input('type');
            $file_name_format = "時間帯区分_%d.%s";
            $file_name        = sprintf($file_name_format, date('YmdHis'), $type);
            $path             = 'master/' . $file_name;
            $timezone_data    = TimeZone::query();

            $csv_data = $timezone_data->get();
            $excel_data = $timezone_data->get()->collect()->map(function ($item) {
                return [
                    'id'         => $item->id,
                    'name'       => $item->detail_times,
                    'created_at' => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($item->updated_at)->format('Y-m-d H:i:s'),
                ];
            });
            $heading = ["ID", "時間帯", "登録日時", "変更日時"];
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
            Excel::import(new TimezoneImport, $csv);
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
