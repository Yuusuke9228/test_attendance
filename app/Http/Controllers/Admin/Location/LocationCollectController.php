<?php

namespace App\Http\Controllers\Admin\Location;

use App\Helper\Helper;
use App\Http\Controllers\Controller;
use App\Models\DakouChild;
use App\Models\DakouData;
use App\Models\Occupation;
use App\Models\Organization;
use App\Models\TimeZone;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LocationCollectController extends Controller
{
    public function index()
    {
        $organizationList = Organization::all();
        $locations = WorkLocation::where('location_flag', true)->get();
        return Inertia::render('Admin/LocationCollect/Index', compact('locations', 'organizationList'));
    }
    public function collect(Request $request)
    {

        $start_date   = $request->input('startDate');
        $close_date   = $request->input('closeDate');
        $location_id  = $request->input('location');
        $organization = $request->input('organization');
        $year         = explode('/', $start_date)[0];

        $jp_year      = Helper::getJpDate($year, 'year');

        $date_arr = [];
        $s_date = Carbon::parse($start_date);
        $c_date = Carbon::parse($close_date);
        while ($s_date->isBefore(Carbon::parse($c_date)->addDay())) {
            $date_arr[] = [
                'date'        => $s_date->format('Y-m-d'),
                'sum_by_date' => 0
            ];
            $s_date = Carbon::parse($s_date)->addDay();
        }

        $location_name = WorkLocation::find($location_id)->location_name;
        if ($organization) {
            $org_id_list = [Organization::find($organization['id'])];
        } else {
            $org_id_list = Organization::all();
        }

        /**
         * ※注意
         * 時間帯選択時、「1日」は1、「午前」、「午後」はそれぞれ0.5
         * 各時間帯が繰り返し入力される場合は、0.5/n
         */
        $data_by_occp = [];
        foreach ($org_id_list as $or_val) {
            $dakoku_data = DakouChild::with(['dakoku', 'occupation', 'work_location', 'work_content', 'dakoku.user.user_data.break_times.organization'])
                ->whereHas('dakoku', function ($query) use ($year, $start_date, $close_date) {
                    $query->whereDate('target_date', '>=', $start_date);
                    $query->whereDate('target_date', '<=', $close_date);
                })->whereHas('dakoku.user.user_data.break_times.organization', function ($query) use ($or_val) {
                    $query->where('id', $or_val->id);
                })->where('dp_work_location_id', $location_id)
                ->where('dp_work_contens_id', '!=', null)
                ->where('dp_occupation_id', '!=', null)
                ->get();
            $org_name     = Organization::find($or_val->id)->organization_name;
            $org_name     = str_replace("株式会社", "", $org_name);
            $header_title = sprintf("㈱%s %s現場の集計データ　%s～%s", $org_name, $location_name, $start_date, $close_date);
            if (!isset($data_by_occp[$or_val->id])) {
                $data_by_occp[$or_val->id] = [
                    'org_id'   => $or_val->id,
                    'org_name' => $or_val->organization_name,
                    'title'    => $header_title,
                    'occps'    => []
                ];
            }
            foreach ($dakoku_data as $key => $val) {
                if (!isset($data_by_occp[$or_val->id]['occps'][$val->dp_occupation_id])) {
                    $nums_of_people_arr = [];
                    $nums_by_occp = 0;
                    foreach ($date_arr as $key => &$date_val) {
                        $data = DakouChild::with(['dakoku', 'occupation', 'work_location', 'dakoku.user.user_data.break_times.organization'])
                            ->whereHas('dakoku', function ($query) use ($date_val, $or_val) {
                                $query->whereDate('target_date', $date_val['date']);
                                $query->whereHas('user.user_data.break_times.organization', function ($q) use ($or_val) {
                                    $q->where('id', $or_val->id);
                                });
                            })
                            ->where(function ($q) use ($location_id, $val) {
                                $q->where('dp_support_flg', 2);
                                $q->where('dp_nums_of_people', '!=', null);
                                $q->where('dp_work_location_id', $location_id);
                                $q->where('dp_occupation_id', $val->dp_occupation_id);
                            })
                            ->first();
                        // 詳細打刻数
                        $nums_of_people_arr[date('n/j', strtotime($date_val['date']))] = $data?->dp_nums_of_people ?? 0;
                        if ($data) {
                            $nums_by_occp += $data->dp_nums_of_people;
                            $date_val['sum_by_date'] += $data->dp_nums_of_people;
                        }
                    }
                    $data_by_occp[$or_val->id]['occps'][$val->dp_occupation_id] = [
                        'occp_id'                => $val->dp_occupation_id,
                        'occp_name'              => $val->occupation->occupation_name,
                        'nums_of_people'         => $nums_of_people_arr,
                        'sum_of_people_by_occps' => $nums_by_occp,
                        'work_contents'          => []
                    ];
                }
                if (!isset($data_by_occp[$or_val->id]['occps'][$val->dp_occupation_id]['work_contents'][$val->dp_work_contens_id]) && $val->dp_work_contens_id) {
                    $row_data = [];
                    $sum_by_contents = 0;
                    foreach ($date_arr as $d_val) {
                        $parent_id = $val->dakoku->id;
                        // Back pagation
                        $parent = DakouData::with('dakoku_children')->find($parent_id);
                        $type = [1 => '1日', 2 => '午前', 3 => '午後'];
                        $tz_id_1 = TimeZone::where('detail_times', $type[1])->first()?->id;
                        $tz_id_2 = TimeZone::where('detail_times', $type[2])->first()?->id;
                        $tz_id_3 = TimeZone::where('detail_times', $type[3])->first()?->id;

                        // calc indexing rating value of location
                        $indexing_value = count($parent->dakoku_children->filter(function ($item) use ($tz_id_1) {
                            return $item->dp_timezone_id != null;
                        }));


                        $child_by_date = DakouChild::with(['dakoku', 'occupation', 'work_location', 'work_content', 'dakoku.user.user_data.break_times.organization'])
                            ->whereHas('dakoku', function ($query) use ($d_val) {
                                $query->whereDate('target_date', $d_val['date']);
                            })->whereHas('dakoku.user.user_data.break_times.organization', function ($query) use ($or_val) {
                                $query->where('id', $or_val->id);
                            })->where('dp_work_location_id', $location_id)
                            ->where('dp_timezone_id', '!=', null)
                            ->where('dp_work_contens_id', $val->dp_work_contens_id)
                            ->where('dp_occupation_id',  $val->dp_occupation_id)
                            ->get();
                        $count = 0;

                        foreach ($child_by_date as $c_val) {
                            $count += round(1 / $indexing_value, 1);
                        }
                        if (!isset($data_by_occp[$or_val->id]['occps'][$val->dp_occupation_id]['work_contents'][$val->dp_work_contens_id]['row_data'][date('j', strtotime($d_val['date']))])) {
                            $row_data[date('n/j', strtotime($d_val['date']))] = $count;
                            if ($count > 0) {
                                $sum_by_contents += $count;
                            }
                        }
                    }
                    $data_by_occp[$or_val->id]['occps'][$val->dp_occupation_id]['work_contents'][$val->dp_work_contens_id] = [
                        'woc_id'         => $val->dp_work_contens_id,
                        'woc_name'       => $val->work_content?->work_content_name,
                        'row_data'       => $row_data,
                        'sum_by_content' => $sum_by_contents
                    ];
                }
            }
        }

        $all_data     = [];
        foreach($data_by_occp as $org_v) {
            foreach($org_v['occps'] as $ocp_k => $ocp_v) {
                if(!isset($all_data[$ocp_k])) {
                    $all_data[$ocp_k] = [
                        'occp_id'                => $ocp_k,
                        'occp_name'              => $ocp_v['occp_name'],
                        'nums_of_people'         => $ocp_v['nums_of_people'],
                        'work_contents'          => $ocp_v['work_contents'],
                        'sum_of_people_by_occps' => $ocp_v['sum_of_people_by_occps'],
                    ];
                } else {
                    $all_data[$ocp_k]['sum_of_people_by_occps'] += $ocp_v['sum_of_people_by_occps'];
                    foreach($ocp_v['nums_of_people'] as $npk => $npv) {
                        $all_data[$ocp_k]['nums_of_people'][$npk] += $npv;
                    }
                    foreach($ocp_v['work_contents'] as $wck => $wcv) {
                        foreach($wcv['row_data'] as $rk => $rv) {
                            if(isset($all_data[$ocp_k]['work_contents'][$wck]['row_data'][$rk])) {
                                $all_data[$ocp_k]['work_contents'][$wck]['row_data'][$rk] += $rv;
                            }
                        }
                        if(isset($all_data[$ocp_k]['work_contents'][$wck]['sum_by_content'])) {
                            $all_data[$ocp_k]['work_contents'][$wck]['sum_by_content'] += $wcv['sum_by_content'];
                        }
                    }
                }
            }
        }

        $all_data = array_values($all_data);
        foreach ($all_data as &$occps) {
            $occps['work_contents'] = array_values($occps['work_contents']);
        }

        $data_by_occp = array_values($data_by_occp);
        foreach ($data_by_occp as &$org) {
            $org['occps'] = array_values($org['occps']);
            foreach ($org['occps'] as &$contents) {
                $contents['work_contents'] = array_values($contents['work_contents']);
            }
        }
        return response()->json(['result' => $data_by_occp, 'all_data' => $all_data, 'date_arr' => $date_arr]);
    }
}
