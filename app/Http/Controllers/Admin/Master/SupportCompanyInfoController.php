<?php

namespace App\Http\Controllers\Admin\Master;

use App\Helper\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CsvFileRequest;
use App\Http\Requests\SupportCompanyRequest;
use App\Imports\SupportCompanyImport;
use App\Models\SupportCompany;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SupportCompanyInfoController extends Controller
{
    public function index(Request $request)
    {
        $key            = $request->input("key");
        $id             = $request->input("id");
        $cop            = $request->input("cop");
        $boss           = $request->input("boss");
        $mail           = $request->input("mail");
        $tel            = $request->input("tel");
        $zip            = $request->input("zip");
        $addr           = $request->input("addr");
        $supportCompany = SupportCompany::when($key, function ($query) use ($key) {
            return $query->where("support_company_name", "like", "%" . $key . "%")
                ->orWhere("support_company_person", "like", "%" . $key . "%")
                ->orWhere("support_company_email", "like", "%" . $key . "%")
                ->orWhere("support_company_tel", "like", "%" . $key . "%")
                ->orWhere("support_company_zipcode", "like", "%" . $key . "%")
                ->orWhere("support_company_address", "like", "%" . $key . "%");
        })->when($id, function ($q) use ($id) {
            $q->where('id', $id);
        })->when($cop, function ($q) use ($cop) {
            $q->where('support_company_name', "like",  "%" . $cop . "%");
        })->when($boss, function ($q) use ($boss) {
            $q->where('support_company_person', "like",  "%" . $boss . "%");
        })->when($mail, function ($q) use ($mail) {
            $q->where('support_company_email', "like",  "%" . $mail . "%");
        })->when($tel, function ($q) use ($tel) {
            $q->where('support_company_tel', "like",  "%" . $tel . "%");
        })->when($zip, function ($q) use ($zip) {
            $q->where('support_company_zipcode', "like",  "%" . $zip . "%");
        })->when($addr, function ($q) use ($addr) {
            $q->where('support_company_address', "like",  "%" . $addr . "%");
        })->paginate(100);
        return Inertia::render('Admin/Master/SupportCompany/SupportCompanyIndex', compact('supportCompany'));
    }
    public function create()
    {
        return Inertia::render('Admin/Master/SupportCompany/CreateSupportCompany');
    }
    public function store(SupportCompanyRequest $request)
    {
        if ($request->companyEmail) {
            $request->validate(
                [
                    'companyEmail' => 'string|max:255|email'
                ],
                [
                    'companyEmail.email' => 'メール形式が正しくありません。'
                ],

            );
        }
        if ($request) {
            $support_company = SupportCompany::create([
                'support_company_name'      => $request->companyName,
                'support_company_person'    => $request->companyPerson,
                'support_company_email'     => $request->companyEmail,
                'support_company_tel'       => $request->companyTel,
                'support_company_zipcode'   => $request->companyZipCode,
                'support_company_address'   => $request->companyAddress,
            ]);

            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.support_company.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.support_company.show', ['id' => $support_company->id]);
            } else {
                return redirect()->route('admin.master.support_company.create');
            }
        }
    }
    public function show($id)
    {
        $supportCompanyDetail = SupportCompany::find($id);
        return Inertia::render('Admin/Master/SupportCompany/SupportCompanyDetail', compact('supportCompanyDetail'));
    }
    public function edit($id)
    {
        $supportCompanyDetail = SupportCompany::find($id);
        return Inertia::render('Admin/Master/SupportCompany/SupportCompanyEdit', compact('supportCompanyDetail'));
    }
    public function update(SupportCompanyRequest $request)
    {
        if ($request->companyEmail) {
            $request->validate(
                [
                    'companyEmail' => 'email'
                ],
                [
                    'companyEmail.email' => 'メール形式が正しくありません。'
                ],

            );
        }
        if ($request) {
            SupportCompany::where('id', $request->id)->update([
                'support_company_name'      => $request->companyName,
                'support_company_person'    => $request->companyPerson,
                'support_company_email'     => $request->companyEmail,
                'support_company_tel'       => $request->companyTel,
                'support_company_zipcode'   => $request->companyZipCode,
                'support_company_address'   => $request->companyAddress,
            ]);

            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.support_company.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.support_company.show', ['id' => $request->id]);
            } else {
                return redirect()->route('admin.master.support_company.create');
            }
        }
    }
    public function destroy(Request $request)
    {
        if ($request) {
            SupportCompany::where('id', $request->id)->delete();
            return redirect()->route('admin.master.support_company.index');
        }
    }
    public function export(Request $request)
    {
        try {
            $type                 = $request->input('type');
            $file_name_format     = "応援に来てもらう会社_%d.%s";
            $file_name            = sprintf($file_name_format, date('YmdHis'), $type);
            $path                 = 'master/' . $file_name;
            $support_company_data = SupportCompany::query();

            $csv_data   = $support_company_data->get();
            $excel_data = $support_company_data->get()->collect()->map(function ($item) {
                return [
                    'id'                      => $item->id,
                    'support_company_name'    => $item->support_company_name,
                    'support_company_person'  => $item->support_company_person,
                    'support_company_email'   => $item->support_company_email,
                    'support_company_tel'     => $item->support_company_tel,
                    'support_company_zipcode' => $item->support_company_zipcode,
                    'support_company_address' => $item->support_company_address,
                    'created_at'              => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
                    'updated_at'              => Carbon::parse($item->updated_at)->format('Y-m-d H:i:s'),
                ];
            });
            $heading = ["ID", "応援会社名", "担当者", "メール", "電話", "郵便番号", "住所", "作成日時", "更新日時"];
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
            Excel::import(new SupportCompanyImport, $csv);
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
