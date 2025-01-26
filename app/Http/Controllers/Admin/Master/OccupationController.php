<?php

namespace App\Http\Controllers\Admin\Master;

use App\Helper\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CsvFileRequest;
use App\Http\Requests\OccupationRequest;
use App\Imports\OccupationImport;
use App\Models\Occupation;
use App\Models\WorkContent;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class OccupationController extends Controller
{
    public function index(Request $request)
    {
        $key = $request->input("key");
        $id = $request->input("id");
        $occp = $request->input("occp");
        $occupation = Occupation::when($key, function ($query) use ($key) {
            return $query->where("occupation_name", "like", "%" . $key . "%");
        })->when($id, function($q) use ($id) {
            $q->where("id", "like", "%". $id . "%");
        })->when($occp, function ($q) use ($occp) {
            $q->where("occupation_name", "LIKE", "%".$occp. "%");
        })->paginate(100);
        return Inertia::render('Admin/Master/Occupation/OccupationIndex', compact('occupation'));
    }

    public function create()
    {
        return Inertia::render('Admin/Master/Occupation/CreateOccupation');
    }
    public function store(OccupationRequest $request)
    {
        if ($request) {
            $occupation =  Occupation::create([
                'occupation_name' => $request->name
            ]);

            // store work_contents, if exists
            $occp_child = $request->workContent;
            if (!empty($request->workContent)) {
                $request->validate(
                    ['workContent.*.workContentName' =>'required|string'],
                    ['workContent.*.workContentName.required' =>'作業内容を入力してください。'],
                );
                foreach ($occp_child as $val) {
                    if ($val['workContentName'] !== null) {
                        WorkContent::create([
                            'work_content_occp_id' => $occupation->id,
                            'work_content_name'    => $val['workContentName'],
                        ]);
                    }
                }
            }

            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.occupation.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.occupation.show', ['id' => $occupation->id]);
            } else {
                return redirect()->route('admin.master.occupation.create');
            }
        }
    }
    public function show($id)
    {
        $occupationDetail = Occupation::with('work_contents')->find($id);
        return Inertia::render('Admin/Master/Occupation/OccupationDetail', compact('occupationDetail'));
    }
    public function edit($id)
    {
        $occupationDetail = Occupation::with('work_contents')->find($id);
        return Inertia::render('Admin/Master/Occupation/OccupationEdit', compact('occupationDetail'));
    }
    public function update(OccupationRequest $request)
    {
        if ($request) {
            $occp_id   = $request->id;
            $occp_name = $request->name;
            Occupation::where('id', $occp_id)->update([
                'occupation_name' => $occp_name
            ]);
            $old_children = Occupation::find($occp_id);
            if (!empty($request->workContent)) {
                $request->validate(
                    ['workContent.*.workContentName' =>'required|string'],
                    ['workContent.*.workContentName.required' =>'作業内容を入力してください。'],
                );
                $old_child_id_arr = $old_children->work_contents->collect()->map(function ($item) {
                    return $item['id'];
                })->toArray();
                $new_child_id_arr = [];
                foreach ($request->workContent as $item) {
                    $new_child_id_arr[] = $item['id'];
                }
                $id_arr_to_remove = array_diff($old_child_id_arr, $new_child_id_arr);

                // remove child
                WorkContent::whereIn('id', $id_arr_to_remove)->delete();

                // add new child
                foreach ($request->workContent as $val) {
                    if ($val['id'] == null) {
                        WorkContent::updateOrCreate([
                            'work_content_occp_id' => $request->id,
                            'work_content_name' => $val['workContentName'],
                        ]);
                    } else {
                        WorkContent::find($val['id'])->update(['work_content_name' => $val['workContentName'],]);
                    }
                }
            } else {
                WorkContent::where('work_content_occp_id', $occp_id)->delete();
            }
            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.occupation.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.occupation.show', ['id' => $request->id]);
            } else {
                return redirect()->route('admin.master.occupation.create');
            }
        }
    }
    public function destroy(Request $request)
    {
        if ($request) {
            Occupation::where('id', $request->id)->delete();
            return redirect()->route('admin.master.occupation.index');
        }
    }
    public function export(Request $request)
    {
        try {
            $type             = $request->input('type');
            $file_name_format = "職種管理_%d.%s";
            $file_name        = sprintf($file_name_format, date('YmdHis'), $type);
            $path             = 'master/' . $file_name;
            $occp_data        = Occupation::query();

            $csv_data   = $occp_data->get();
            $excel_data = $occp_data->get()->collect()->map(function ($item) {
                return [
                    'id'               => $item->id,
                    'occupation_name'    => $item->occupation_name,
                    'created_at'       => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
                    'updated_at'       => Carbon::parse($item->updated_at)->format('Y-m-d H:i:s'),
                ];
            });
            $heading = ["ID", "職種名", "作成日時", "更新日時"];
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
            Excel::import(new OccupationImport, $csv);
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
