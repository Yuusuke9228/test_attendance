<?php

namespace App\Http\Controllers\Admin\Master;

use App\Helper\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CsvFileRequest;
use App\Http\Requests\OrganizationRequest;
use App\Imports\OrganizationImport;
use App\Models\Organization;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $key          = $request->input("key");
        $id           = $request->input("id");
        $parent       = $request->input("parent");
        $code         = $request->input("code");
        $org          = $request->input("org");
        $organization = Organization::with('parentOrg')->when($key, function ($query) use ($key) {
            return $query->where("organization_code", "like", "%" . $key . "%")
                ->orWhere("organization_name", "like", "%" . $key . "%")
                ->orWhere("organization_parent_name", "like", "%" . $key . "%");
        })->when($id, function ($q) use ($id) {
            $q->where('id', $id);
        })->when($code, function ($q) use ($code) {
            $q->where('organization_code', "like",  "%" . $code . "%");
        })->when($org, function ($q) use ($org) {
            $q->where('organization_name', "like",  "%" . $org . "%");
        })->when($parent, function ($q) use ($parent) {
            $q->where('organization_parent_name', "like",  "%" . $parent . "%");
        })->paginate(100);
        return Inertia::render('Admin/Master/Organization/OrganizationIndex', compact('organization'));
    }
    public function create()
    {
        $organizationList = Organization::all();
        return Inertia::render('Admin/Master/Organization/CreateOrganization', compact('organizationList'));
    }
    public function store(OrganizationRequest $request)
    {
        if ($request) {
            $organization = Organization::create([
                'organization_parent_id'   => $request->proOrganization ? $request->proOrganization['id'] : null,
                'organization_parent_name' => $request->proOrganization ? $request->proOrganization['organization_name'] : null,
                'organization_code'        => $request->organizationCode,
                'organization_name'        => $request->organizationName,
                'organization_zipcode'     => $request->organizationZipCode,
                'organization_address'     => $request->organizationAddress,
                'organization_master_name' => $request->organizationMaster,
            ]);

            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.organization.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.organization.show', ['id' => $organization->id]);
            } else {
                return redirect()->route('admin.master.organization.create');
            }
        }
    }
    public function show($id)
    {
        $organizationDetail = Organization::find($id);
        return Inertia::render('Admin/Master/Organization/OrganizationDetail', compact('organizationDetail'));
    }
    public function edit($id)
    {
        $organizationList = Organization::all();
        $organizationDetail = Organization::find($id);
        return Inertia::render('Admin/Master/Organization/OrganizationEdit', compact('organizationDetail', 'organizationList'));
    }
    public function update(OrganizationRequest $request)
    {
        if ($request) {
            Organization::where('id', $request->id)->update([
                'organization_parent_id'   => $request->proOrganization ? $request->proOrganization['id'] : null,
                'organization_parent_name' => $request->proOrganization ? $request->proOrganization['organization_name'] : null,
                'organization_code'        => $request->organizationCode,
                'organization_name'        => $request->organizationName,
                'organization_zipcode'     => $request->organizationZipCode,
                'organization_address'     => $request->organizationAddress,
                'organization_master_name' => $request->organizationMaster,
            ]);

            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.organization.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.organization.show', ['id' => $request->id]);
            } else {
                return redirect()->route('admin.master.organization.create');
            }
        }
    }
    public function destroy(Request $request)
    {
        if ($request) {
            Organization::where('id', $request->id)->delete();
            return redirect()->route('admin.master.organization.index');
        }
    }
    public function export(Request $request)
    {
        try {
            $type             = $request->input('type');
            $file_name_format = "組織_%d.%s";
            $file_name        = sprintf($file_name_format, date('YmdHis'), $type);
            $path             = 'master/' . $file_name;
            $organizations    = Organization::query();

            $excel_data = $organizations->get()->collect()->map(function ($item) {
                return [
                    'id'                       => $item->id,
                    'organization_parent_name' => $item->organization_parent_name,
                    'organization_code'        => $item->organization_code,
                    'organization_name'        => $item->organization_name,
                    'organization_zipcode'     => $item->organization_zipcode,
                    'organization_address'     => $item->organization_address,
                    'organization_master_name' => $item->organization_master_name,
                    'created_at'               => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
                    'updated_at'               => Carbon::parse($item->updated_at)->format('Y-m-d H:i:s'),
                ];
            });
            $heading = ["ID", "親組織", "組織コード", "組織名", "郵便番号", "住所", "代表者名", "作成日時", "更新日時"];
            if ($type == 'csv') {
                $status = ExcelExport::exportCsv($excel_data, $heading, $path);
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
            Excel::import(new OrganizationImport, $csv);
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
