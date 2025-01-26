<?php

namespace App\Http\Controllers\Admin\BasicInfo;

use App\Http\Controllers\Controller;
use App\Http\Requests\BasicInfoRequest;
use App\Models\CompanySetting;
use App\Models\PrefectureList;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BasicInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $prefecutres = PrefectureList::all();
        $companySetting = CompanySetting::first();
        return Inertia::render('Admin/BasicInfo/BasicInfoIndex', compact('prefecutres', 'companySetting'));
    }

    public function create()
    {
        //
    }

    public function update(BasicInfoRequest $request)
    {
        $close_date  = $request->closeDate;
        $year  = date('Y', strtotime($close_date));
        $month = date('m', strtotime($close_date));
        $close_date = Carbon::create($year, $month, 1)->format("Y-m-d");
        CompanySetting::first()->update([
            'company_name'                 => $request->name,
            'company_kana'                 => $request->kanaName,
            'company_zip_code'             => $request->zipCode,
            'company_tel01'                => $request->tel_1,
            'company_tel02'                => $request->tel_2,
            'company_tel03'                => $request->tel_3,
            'company_addr01'               => $request->address_1,
            'company_addr02'               => $request->address_2,
            'company_month_closing_status' => $request->closeStatus,
            'company_month_closing_date'   => $close_date,
        ]);
        return redirect()->route('admin.base.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
