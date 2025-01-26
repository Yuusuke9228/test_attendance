<?php

namespace App\Http\Controllers\Admin\Master;

use App\Helper\ExcelExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CsvFileRequest;
use App\Http\Requests\SupportCompanyRequest;
use App\Imports\SupportedCompanyImport;
use App\Models\SupportedCompany;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SupportedCompanyInfoController extends Controller
{
    public function index(Request $request)
    {
        $key              = $request->input("key");
        $id               = $request->input("id");
        $cop              = $request->input("cop");
        $boss             = $request->input("boss");
        $mail             = $request->input("mail");
        $tel              = $request->input("tel");
        $zip              = $request->input("zip");
        $addr             = $request->input("addr");
        $supportedCompany = SupportedCompany::when($key, function ($query) use ($key) {
            return $query->where("supported_company_name", "like", "%" . $key . "%")
                ->orWhere("supported_company_person", "like", "%" . $key . "%")
                ->orWhere("supported_company_email", "like", "%" . $key . "%")
                ->orWhere("supported_company_tel", "like", "%" . $key . "%")
                ->orWhere("supported_company_zipcode", "like", "%" . $key . "%")
                ->orWhere("supported_company_address", "like", "%" . $key . "%");
        })->when($id, function ($q) use ($id) {
            $q->where('id', $id);
        })->when($cop, function ($q) use ($cop) {
            $q->where('supported_company_name', "like",  "%" . $cop . "%");
        })->when($boss, function ($q) use ($boss) {
            $q->where('supported_company_person', "like",  "%" . $boss . "%");
        })->when($mail, function ($q) use ($mail) {
            $q->where('supported_company_email', "like",  "%" . $mail . "%");
        })->when($tel, function ($q) use ($tel) {
            $q->where('supported_company_tel', "like",  "%" . $tel . "%");
        })->when($zip, function ($q) use ($zip) {
            $q->where('supported_company_zipcode', "like",  "%" . $zip . "%");
        })->when($addr, function ($q) use ($addr) {
            $q->where('supported_company_address', "like",  "%" . $addr . "%");
        })->paginate(100);
        return Inertia::render('Admin/Master/SupportedCompany/SupportedCompanyIndex', compact('supportedCompany'));
    }
    public function create()
    {
        return Inertia::render('Admin/Master/SupportedCompany/CreateSupportedCompany');
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
            $supported_company = SupportedCompany::create([
                'supported_company_name'      => $request->companyName,
                'supported_company_person'    => $request->companyPerson,
                'supported_company_email'     => $request->companyEmail,
                'supported_company_tel'       => $request->companyTel,
                'supported_company_zipcode'   => $request->companyZipCode,
                'supported_company_address'   => $request->companyAddress,
            ]);

            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.supported_company.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.supported_company.show', ['id' => $supported_company->id]);
            } else {
                return redirect()->route('admin.master.supported_company.create');
            }
        }
    }
    public function show($id)
    {
        $supportedCompanyDetail = SupportedCompany::find($id);
        return Inertia::render('Admin/Master/SupportedCompany/SupportedCompanyDetail', compact('supportedCompanyDetail'));
    }
    public function edit($id)
    {
        $supportedCompanyDetail = SupportedCompany::find($id);
        return Inertia::render('Admin/Master/SupportedCompany/SupportedCompanyEdit', compact('supportedCompanyDetail'));
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
            SupportedCompany::where('id', $request->id)->update([
                'supported_company_name'      => $request->companyName,
                'supported_company_person'    => $request->companyPerson,
                'supported_company_email'     => $request->companyEmail,
                'supported_company_tel'       => $request->companyTel,
                'supported_company_zipcode'   => $request->companyZipCode,
                'supported_company_address'   => $request->companyAddress,
            ]);

            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.supported_company.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.supported_company.show', ['id' => $request->id]);
            } else {
                return redirect()->route('admin.master.supported_company.create');
            }
        }
    }
    public function destroy(Request $request)
    {
        if ($request) {
            SupportedCompany::where('id', $request->id)->delete();
            return redirect()->route('admin.master.supported_company.index');
        }
    }
    public function export(Request $request)
    {
        try {
            $type                 = $request->input('type');
            $file_name_format     = "応援に行く先の会社_%d.%s";
            $file_name            = sprintf($file_name_format, date('YmdHis'), $type);
            $path                 = 'master/' . $file_name;
            $support_company_data = SupportedCompany::query();

            $csv_data   = $support_company_data->get();
            $excel_data = $support_company_data->get()->collect()->map(function ($item) {
                return [
                    'id'                        => $item->id,
                    'supported_company_name'    => $item->supported_company_name,
                    'supported_company_person'  => $item->supported_company_person,
                    'supported_company_email'   => $item->supported_company_email,
                    'supported_company_tel'     => $item->supported_company_tel,
                    'supported_company_zipcode' => $item->supported_company_zipcode,
                    'supported_company_address' => $item->supported_company_address,
                    'created_at'                => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
                    'updated_at'                => Carbon::parse($item->updated_at)->format('Y-m-d H:i:s'),
                ];
            });
            $heading = ["ID", "応援先会社名", "担当者", "メール", "電話", "郵便番号", "住所", "作成日時", "更新日時"];
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
            Excel::import(new SupportedCompanyImport, $csv);
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
