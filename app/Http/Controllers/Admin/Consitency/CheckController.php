<?php

namespace App\Http\Controllers\Admin\Consitency;

use App\Http\Controllers\Controller;
use App\Models\DakouChild;
use App\Models\DakouData;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Consistency/Index');
    }

    public function fetch_data(Request $request)
    {
        $data = DakouChild::with(['dakoku.attend_type', 'dakoku.user', 'work_location', 'occupation', 'support_company', 'supported_company'])
            ->where('dp_work_location_id', '!=', null)
            ->where('dp_work_contens_id', '!=', null)
            ->where('dp_occupation_id', '!=', null)
            ->whereIn('dp_support_flg', [1, 2])
            ->whereRelation('dakoku.attend_type', 'attend_type_name', '!=', '未出勤')
            ->get();
        $wrong_data = [];
        foreach ($data as $key => $val) {
            $date                = Carbon::parse($val->dakoku->target_date)->format('Y-m-d');
            $loc_id              = $val->dp_work_location_id;
            $loc_name            = $val->work_location->location_name;
            $occp_id             = $val->dp_occupation_id;
            $occp_name           = $val->occupation->occupation_name;
            $user_id             = $val->dakoku->dp_user;
            $user_name           = $val->dakoku->user->name;
            $flag                = $val->dp_support_flg;

            if ($flag === 1) {
                $comp_id   = $val?->dp_supported_company_id;
                $comp_name = $val?->supported_company?->supported_company_name;
            } else {
                $comp_id   = $val?->dp_support_company_id;
                $comp_name = $val?->support_company?->support_company_name;
            }

            if (!isset($wrong_data[$date])) {
                $wrong_data[$date] = [
                    'date'     => $date,
                    'location' => []
                ];
            }

            if (!isset($wrong_data[$date]['location'][$loc_id])) {
                $wrong_data[$date]['location'][$loc_id] = [
                    'loc_id'     => $loc_id,
                    'loc_name'   => $loc_name,
                    'occupation' => []
                ];
            }

            if (!isset($wrong_data[$date]['location'][$loc_id]['occupation'][$occp_id])) {
                $wrong_data[$date]['location'][$loc_id]['occupation'][$occp_id] = [
                    'occp_id'   => $occp_id,
                    'occp_name' => $occp_name,
                    'comp'      => []
                ];
            }

            if (!isset($wrong_data[$date]['location'][$loc_id]['occupation'][$occp_id]['comp'][$flag])) {
                $wrong_data[$date]['location'][$loc_id]['occupation'][$occp_id]['comp'][$flag] = [
                    'flag'      => $flag,
                    'flag_name' => $flag == 1 ? '応援に行った先' : ($flag == 2 ? '応援来てもらった先' : 'なし'),
                    'comp_detail'      => []
                ];
            }
            if (!isset($wrong_data[$date]['location'][$loc_id]['occupation'][$occp_id]['comp'][$flag]['comp_detail'][$comp_id])) {
                $wrong_data[$date]['location'][$loc_id]['occupation'][$occp_id]['comp'][$flag]['comp_detail'][$comp_id] = [
                    'comp_id'   => $comp_id,
                    'comp_name' => $comp_name,
                    'users'     => []
                ];
            }
            $nums_array = DakouChild::with('dakoku')
                ->whereHas('dakoku', function ($query) use ($date) {
                    $query->whereDate('target_date', $date);
                })
                ->where('dp_occupation_id', $occp_id)
                ->where('dp_work_location_id', $loc_id)
                ->where('dp_support_flg', $flag)
                ->where(function ($query) use ($flag, $comp_id) {
                    if($comp_id) {
                        if ($flag == 1) {
                            $query->where('dp_supported_company_id', $comp_id);
                        } else {
                            $query->where('dp_support_company_id', $comp_id);
                        }
                    }
                })->pluck('dp_nums_of_people')->toArray();
            if (count(array_unique($nums_array)) !== 1) {
                if (!isset($wrong_data[$date]['location'][$loc_id]['occupation'][$occp_id]['comp'][$flag]['comp_detail'][$comp_id]['users'][$user_id])) {
                    $wrong_data[$date]['location'][$loc_id]['occupation'][$occp_id]['comp'][$flag]['comp_detail'][$comp_id]['users'][$user_id] = [
                        'data_id'   => $val->dakoku->id,
                        'user_id'   => $user_id,
                        'user_name' => $user_name,
                        'nums'      => $val->dp_nums_of_people,
                    ];
                }
            }
        }

        // 応援人数が異なる打刻データをグループ化
        $final_data = [];
        foreach ($wrong_data as $val) {
            foreach ($val['location'] as $loc_val) {
                foreach ($loc_val['occupation'] as $occps) {
                    foreach ($occps['comp'] as $comp) {
                        foreach($comp['comp_detail'] as $users) {
                            foreach($users['users'] as $f_val) {
                                if(array_key_exists('user_id', $f_val)) {
                                    $new_arr  = [
                                        'id'         => $f_val['data_id'],
                                        'date'       => $val['date'],
                                        'user'       => $f_val['user_name'],
                                        'location'   => $loc_val['loc_name'],
                                        'occupation' => $occps['occp_name'],
                                        'flag'       => $comp['flag_name'],
                                        'comp_name'  => $users['comp_name'],
                                        'nums'       => $f_val['nums'],
                                    ];
                                    array_push($final_data, $new_arr);
                                }
                            }
                        }
                    }
                }
            }
        }
        // sort by target date
        usort($final_data, function ($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        return response()->json($final_data);
    }
}
