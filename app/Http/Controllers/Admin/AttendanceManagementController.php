<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DakokuRequest;
use App\Models\DakouData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AttendanceManagementController extends Controller
{
    public function index(Request $request)
    {
        $start_date = $request->input('startDate');
        $end_date   = $request->input('endDate');
        $page       = $request->input('page');

        $cache_key = "attendance_management_" . md5(serialize([$start_date, $end_date, $page]));

        $dakoku = Cache::remember($cache_key, 60, function () use ($start_date, $end_date) {
            $cached_data = DakouData::with(['attend_type', 'dakoku_children', 'user'])
                ->when($start_date, fn ($query) => $query->whereDate('target_date', '>=', $start_date))
                ->when($end_date, fn ($query) => $query->whereDate('target_date', '<=', $end_date))
                ->orderBy('id', 'DESC')
                ->paginate(50)->withQueryString();
            return $cached_data;
        });
        return Inertia::render('Admin/AttendManage/AttendanceManagement', compact('dakoku'));
    }
}
