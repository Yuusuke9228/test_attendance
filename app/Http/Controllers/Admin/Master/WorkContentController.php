<?php

namespace App\Http\Controllers\Admin\Master;

use App\Helper\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\{CsvFileRequest, WorkContentStoreRequest, WorkContentUpdateRequest};
use App\Imports\WorkContentImport;
use App\Models\Occupation;
use App\Models\WorkContent;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class WorkContentController extends Controller
{
    public function index(Request $request)
    {
        $key         = $request->input("key");
        $id          = $request->input("id");
        $occp        = $request->input("occp");
        $woc         = $request->input("woc");
        $workContent = WorkContent::with('occupation')
            ->when($key, function ($query) use ($key) {
                return $query->where('work_content_name', "like", "%" . $key . "%")
                    ->orWhereHas("occupation", function ($q) use ($key) {
                        $q->where("occupation_name", "like", "%" . $key . "%");
                    });
            })->when($id, function ($q) use ($id) {
                $q->where('id', $id);
            })->when($occp, function ($q) use ($occp) {
                $q->whereRelation('occupation', 'occupation_name', "like", "%" . $occp . "%");
            })->when($woc, function ($q) use ($woc) {
                $q->where('work_content_name', "like", "%" . $woc . "%");
            })->paginate(100);
        return Inertia::render('Admin/Master/WorkContent/WorkContentIndex', compact('workContent'));
    }

    public function create()
    {
        $occupation = Occupation::all();
        return Inertia::render('Admin/Master/WorkContent/CreateWorkContent', compact('occupation'));
    }
    public function store(WorkContentStoreRequest $request)
    {
        if ($request) {
            $work_content = WorkContent::create([
                'work_content_occp_id'   => $request->occupation,
                'work_content_name'      => $request->workContentName
            ]);

            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.work_contents.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.work_contents.show', ['id' => $work_content->id]);
            } else {
                return redirect()->route('admin.master.work_contents.create');
            }
        }
    }
    public function show($id)
    {
        $workContentsDetail = WorkContent::with('occupation')->find($id);
        return Inertia::render('Admin/Master/WorkContent/WorkContentDetail', compact('workContentsDetail'));
    }
    public function edit($id)
    {
        $workContentsDetail = WorkContent::with('occupation')->find($id);
        return Inertia::render('Admin/Master/WorkContent/WorkContentEdit', compact('workContentsDetail'));
    }
    public function update(WorkContentUpdateRequest $request)
    {
        if ($request) {
            WorkContent::where('id', $request->id)->update([
                'work_content_name' => $request->workContentName
            ]);

            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.work_contents.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.work_contents.show', ['id' => $request->id]);
            } else {
                return redirect()->route('admin.master.work_contents.create');
            }
        }
    }
    public function destroy(Request $request)
    {
        if ($request) {
            WorkContent::where('id', $request->id)->delete();
            return redirect()->route('admin.master.work_contents.index');
        }
    }
    public function export(Request $request)
    {
        try {
            $type             = $request->input('type');
            $file_name_format = "作業内容_%d.%s";
            $file_name        = sprintf($file_name_format, date('YmdHis'), $type);
            $path             = 'master/' . $file_name;
            $work_contents    = WorkContent::query();

            $csv_data   = $work_contents->get();
            $excel_data = $work_contents->get()->collect()->map(function ($item) {
                return [
                    'id'                => $item->id,
                    'occupation_name'   => $item->occupation->occupation_name,
                    'work_content_name' => $item->work_content_name,
                    'created_at'        => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
                    'updated_at'        => Carbon::parse($item->updated_at)->format('Y-m-d H:i:s'),
                ];
            });
            $heading = ["ID", "職種管理", "作業名", "作成日時", "更新日時"];
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
            Excel::import(new WorkContentImport, $csv);
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
