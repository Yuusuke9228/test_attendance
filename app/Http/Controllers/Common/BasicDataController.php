<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\AttendStatus;
use App\Models\AttendType;
use App\Models\BreakTime;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\DakouChild;
use App\Models\DakouData;
use App\Models\Occupation;
use App\Models\Organization;
use App\Models\Schedule;
use App\Models\SupportCompany;
use App\Models\SupportedCompany;
use App\Models\TimeZone;
use App\Models\User;
use App\Models\WorkContent;
use App\Models\WorkLocation;
use Illuminate\Http\Request;

class BasicDataController extends Controller
{
    public function fetch(Request $request)
    {
        $attendType         = AttendType::all();
        $attendStatus       = AttendStatus::all();
        $breakTime          = BreakTime::with('organization')->orderBy('id', 'ASC')->get();
        $occupation         = Occupation::all();
        $organiaztion       = Organization::all();
        $supportCompany     = SupportCompany::all();
        $supportedCompany   = SupportedCompany::all();
        $workContent        = WorkContent::all();
        $workLocation       = WorkLocation::where("location_flag", 1)->get();
        $companySettings    = CompanySetting::all();
        $customer           = Customer::all();
        $dakoku             = DakouData::all();
        $dakokuChild        = DakouChild::all();
        $users              = User::all();
        $schedule           = Schedule::all();

        if ($request->table) {
            $tb = $request->table;
            switch ($tb) {
                case 'attendType':
                    return response()->json($attendType);
                case 'attendStatus':
                    return response()->json($attendStatus);
                case 'breakTime':
                    return response()->json($breakTime);
                case 'occupation':
                    return response()->json($occupation);
                case 'organiaztion':
                    return response()->json($organiaztion);
                case 'supportCompany':
                    return response()->json($supportCompany);
                case 'supportedCompany':
                    return response()->json($supportedCompany);
                case 'workContent':
                    return response()->json($workContent);
                case 'workLocation':
                    return response()->json($workLocation);
                case 'dakoku':
                    return response()->json($dakoku);
                case 'users':
                    return response()->json($users);
            }
        } else {
            return response()->json(
                [
                    'attendType'          => $attendType,
                    'attendStatus'        => $attendStatus,
                    'breakTime'           => $breakTime,
                    'occupation'          => $occupation,
                    'organiaztion'        => $organiaztion,
                    'supportCompany'      => $supportCompany,
                    'supportedCompany'    => $supportedCompany,
                    'workContent'         => $workContent,
                    'workLocation'        => $workLocation,
                    'companySettings'     => $companySettings,
                    'customer'            => $customer,
                    'dakoku'              => $dakoku,
                    'dakokuChild'         => $dakokuChild,
                    'users'               => $users,
                    'schedule'            => $schedule
                ]
            );
        }
    }
    public function checkMaster()
    {
        $timezone          = TimeZone::exists();
        $locations         = WorkLocation::exists();
        $occupations       = Occupation::exists();
        $work_contents     = WorkContent::exists();
        $support_company   = SupportCompany::exists();
        $supported_company = SupportedCompany::exists();
        $organization      = Organization::exists();
        if (!$timezone || !$locations || !$occupations || !$work_contents || !$support_company || !$supported_company || !$organization) {
            return response()->json(['success' => false, 'message' => '一部のマスターデータが存在しません。']);
        } else {
            return response()->json(['success' => true, 'message' => 'Ok']);
        }
    }

    /**
     * Params:
     * UserId, Dakoku_date, TimeZone, WorkLocation
     * https://www.chatwork.com/#!rid342808488-1822910905017438208
     */
    public function confirm_attend(Request $request)
    {
        $user_id     = $request->input('user_id');
        $dakoku_date = $request->input('dakoku_date');
        $timezone_id = $request->input('timezone_id');
        $location_id = $request->input('location_id');
        if ($user_id && $dakoku_date && $timezone_id && $location_id) {
            $dakoku_data = DakouChild::with(['dakoku', 'support_company', 'supported_company', 'work_location', 'timezone'])
                ->whereHas('dakoku', function ($query) use ($dakoku_date) {
                    $query->whereDate('target_date', $dakoku_date);
                })->whereJsonContains('dp_workers', (int)$user_id)
                ->where('dp_timezone_id', $timezone_id)
                ->where('dp_work_location_id', $location_id)
                ->first();
            return response()->json($dakoku_data);
        }
    }

    public function recommend_cond(Request $request)
    {
        $user_id     = $request->input('user_id');
        $dakoku_date = $request->input('dakoku_date');
        $timezone_id = $request->input('timezone_id');
        if ($user_id && $dakoku_date && $timezone_id) {
            $dakoku_data = DakouChild::with(['dakoku', 'support_company', 'supported_company', 'work_location', 'timezone'])
                ->whereHas('dakoku', function ($query) use ($dakoku_date) {
                    $query->whereDate('target_date', $dakoku_date);
                })->whereJsonContains('dp_workers', (int)$user_id)
                ->where('dp_timezone_id', $timezone_id)
                ->first();
            // 現場人員：1
            if ($dakoku_data?->dp_workers) {
                //  Aが B、C、Dを現場人員として選択した場合、B側の現場人員にはA、C、Dのみを表示
                // 現場人員として登録されたユーザー
                $workers = json_decode($dakoku_data->dp_workers);
                // 本人は現場人員から削除
                $owner_id_index = array_search($user_id, $workers);
                unset($workers[$owner_id_index]);

                // 打刻を実施しているユーザー
                $dakoku_owner = $dakoku_data->dakoku->user->id;
                array_push($workers, $dakoku_owner);
                $workers_id = User::whereIn('id', $workers)->pluck('id')?->toArray();
                $workers_name = User::whereIn('id', $workers)->pluck('name')?->toArray();
                $workers_name = implode('、', $workers_name);
            }
            // 現場名：2
            if ($dakoku_data?->dp_work_location_id) {
                $work_location = WorkLocation::find($dakoku_data?->dp_work_location_id);
            }
            // 作業責任者：3
            if ($dakoku_data?->dp_workers_master) {
                $master = User::find($dakoku_data?->dp_workers_master);
            }
            // 応援区分, 応援会社, 応援数：
            if ($dakoku_data?->dp_support_flg) {
                $support_flg = $dakoku_data?->dp_support_flg;
                if($support_flg == 1) {
                    $support_flg_name = "応援に行った";
                } else if ($support_flg == 2) {
                    $support_flg_name = "応援に来てもらった";
                }
                $support_comp = SupportCompany::find($dakoku_data?->dp_support_company_id); // flg 2
                $supported_comp = SupportedCompany::find($dakoku_data?->dp_supported_company_id); // flg 1
                $nums_of_people = $dakoku_data?->dp_nums_of_people ?? "なし";
            }
            $data = [
                'worker_ids'     => $workers_id ?? [],
                'worker_names'   => $workers_name ?? "なし",
                'work_location'  => $work_location ?? null,
                'master'         => $master ?? null,
                'flg'            => $support_flg ?? 0,
                'flg_name'       => $support_flg_name ?? null,
                'supported_comp' => $supported_comp ?? null,
                'support_comp'   => $support_comp ?? null,
                'nums_of_people' => $nums_of_people ?? 0,
            ];
            if($dakoku_data) {
                return response()->json(['result' => $data]);
            } else {
                return response()->json(['result' => null]);
            }
        }
    }
}
