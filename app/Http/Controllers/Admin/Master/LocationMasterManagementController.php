<?php

namespace App\Http\Controllers\Admin\Master;

use App\Helper\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CsvFileRequest;
use App\Http\Requests\LocationMasterRequest;
use App\Imports\WorkLocationImport;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class LocationMasterManagementController extends Controller
{
    public function index(Request $request)
    {
        $key      = $request->input("key");
        $id       = $request->input("id");
        $loc      = $request->input("loc");
        $addr     = $request->input("addr");
        $valid    = $request->input("valid");
        $location = WorkLocation::when($key, function ($query) use ($key) {
            $query->where(function ($q) use ($key) {
                if ($key) {
                    $q->where('location_name', "like", "%" . $key . "%");
                    $q->orWhere('location_address', 'like', '%' . $key . '%');
                }
            });
        })->when($id, function ($q) use ($id) {
            $q->where('id', $id);
        })->when($loc, function ($q) use ($loc) {
            $q->where('location_name', 'like',  "%".$loc."%");
        })->when($addr, function ($q) use ($addr) {
            $q->where('location_address',"like", "%".$addr."%");
        })->when($request->filled('valid'), function ($q) use ($valid) {
            $q->where('location_flag', (int)$valid);
        })->paginate(100);
        return Inertia::render('Admin/Master/LocationMaster/LocationIndex', compact('location', 'key'));
    }
    public function create()
    {
        return Inertia::render('Admin/Master/LocationMaster/CreateLocation');
    }
    public function store(LocationMasterRequest $request)
    {
        if ($request) {
            $location =  WorkLocation::create([
                'location_flag'     => $request->flag,
                'location_name'     => $request->locationName,
                'location_address'  => $request->address,
            ]);
            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.location.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.location.show', ['id' => $location->id]);
            } else {
                return redirect()->route('admin.master.location.create');
            }
        }
    }
    public function show($id)
    {
        $locationDetail = WorkLocation::find($id);
        return Inertia::render('Admin/Master/LocationMaster/LocationDetail', compact('locationDetail'));
    }
    public function edit($id)
    {
        $locationDetail = WorkLocation::find($id);
        return Inertia::render('Admin/Master/LocationMaster/LocationEdit', compact('locationDetail'));
    }
    public function update(LocationMasterRequest $request)
    {
        if ($request) {
            $location =  WorkLocation::where("id", $request->id)->update([
                'location_flag'     => $request->flag,
                'location_name'     => $request->locationName,
                'location_address'  => $request->address,
            ]);
            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.location.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.location.show', ['id' => $location->id]);
            } else {
                return redirect()->route('admin.master.location.create');
            }
        }
    }
    public function destroy(Request $request)
    {
        if ($request) {
            WorkLocation::where('id', $request->id)->delete();
            return redirect()->route('admin.master.location.index');
        }
    }
    public function export(Request $request)
    {
        try {
            $type             = $request->input('type');
            $file_name_format = "現場管理_%d.%s";
            $file_name        = sprintf($file_name_format, date('YmdHis'), $type);
            $path             = 'master/' . $file_name;
            $location_data    = WorkLocation::query();

            $csv_data   = $location_data->get();
            $excel_data = $location_data->get()->collect()->map(function ($item) {
                return [
                    'id'               => $item->id,
                    'location_flag'    => $item->location_flag,
                    'location_name'    => $item->location_name,
                    'location_address' => $item->location_address,
                    'created_at'       => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
                    'updated_at'       => Carbon::parse($item->updated_at)->format('Y-m-d H:i:s'),
                ];
            });
            $heading = ["ID","有効無効", "現場名", "住所",  "作成日時", "更新日時"];
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
            Excel::import(new WorkLocationImport, $csv);
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
