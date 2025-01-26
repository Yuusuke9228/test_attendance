<?php

namespace App\Http\Controllers\Admin\GlobalFilter;

use App\Http\Controllers\Controller;
use App\Models\AttendStatus;
use App\Models\BreakTime;
use App\Models\DakouChild;
use App\Models\DakouData;
use App\Models\Occupation;
use App\Models\Schedule;
use App\Models\SupportCompany;
use App\Models\SupportedCompany;
use App\Models\TimeZone;
use App\Models\User;
use App\Models\WorkContent;
use App\Models\WorkLocation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FilterController extends Controller
{
    public function filter(Request $request)
    {
        $keyword = $request->input("s");
        // Filter in dakoudata table
        $dakou_data = DakouData::with(['user', 'attend_type', 'attend_status', 'dakoku_children', 'user.user_data.break_times.organization'])
            ->where(function ($q) use ($keyword) {
                $q->orWhere('dp_memo', 'like', "%" . $keyword . "%");
                $q->orWhere('dp_ride_flg', $keyword);
                $q->orWhereHas('user', function ($sq) use ($keyword) {
                    $sq->Where('code', "like", "%" . $keyword . "%")
                        ->orWhere('name', "like", "%" . $keyword . "%")
                        ->orWhere('email', "like", "%" . $keyword . "%");
                });
                $q->orWhereHas('attend_type', function ($sq) use ($keyword) {
                    $sq->where('attend_type_name', "like", "%" . $keyword . "%");
                });
                $q->orWhereHas('attend_status', function ($sq) use ($keyword) {
                    $sq->where('attend_name', "like", "%" . $keyword . "%");
                });
            })->get();
        $dakou_child =  DakouChild::with([
            'dakoku',
            'dakoku.attend_type',
            'dakoku.attend_status',
            'dakoku.user',
            'support_company',
            'supported_company',
            'occupation',
            'work_content',
            'work_location',
            'timezone',
        ])->where(function ($q) use ($keyword) {
            $q->orWhereHas('dakoku', function ($sq) use ($keyword) {
                $sq->Where('dp_memo', "like", "%" . $keyword . "%")
                    ->orWhere('dp_ride_flg', "like", "%" . $keyword . "%");
            });
            $q->orWhereHas('dakoku.attend_type', function ($sq) use ($keyword) {
                $sq->where('attend_type_name', "like", "%" . $keyword . "%");
            });
            $q->orWhereHas('dakoku.attend_status', function ($sq) use ($keyword) {
                $sq->where('attend_name', "like", "%" . $keyword . "%");
            });
            $q->orWhereHas('dakoku.user', function ($sq) use ($keyword) {
                $sq->Where('code', "like", "%" . $keyword . "%")
                    ->orWhere('name', "like", "%" . $keyword . "%")
                    ->orWhere('email', "like", "%" . $keyword . "%");
            });
            $q->orWhereHas('support_company', function ($sq) use ($keyword) {
                $sq->where('support_company_name', "like", "%" . $keyword . "%");
            });
            $q->orWhereHas('supported_company', function ($sq) use ($keyword) {
                $sq->where('supported_company_name', "like", "%" . $keyword . "%");
            });
            $q->orWhereHas('occupation', function ($sq) use ($keyword) {
                $sq->where('occupation_name', "like", "%" . $keyword . "%");
            });
            $q->orWhereHas('work_content', function ($sq) use ($keyword) {
                $sq->where('work_content_name', "like", "%" . $keyword . "%");
            });
            $q->orWhereHas('work_location', function ($sq) use ($keyword) {
                $sq->where('location_name', "like", "%" . $keyword . "%");
            });
            $q->orWhereHas('timezone', function ($sq) use ($keyword) {
                $sq->where('detail_times', "like", "%" . $keyword . "%");
            });
        })->get();

        $users = User::with('user_data.break_times.organization', 'user_data.break_times')
            ->where(function ($q) use ($keyword) {
                $q->where('code', "like", "%" . $keyword . "%")
                    ->orWhere('name', "like", "%" . $keyword . "%")
                    ->orWhere('email', "like", "%" . $keyword . "%");
                $q->orWhereHas('user_data.break_times.organization', function ($sq) use ($keyword) {
                    $sq->where('organization_name', "like", "%" . $keyword . "%");
                });
                $q->orWhereHas('user_data.break_times', function ($sq) use ($keyword) {
                    $sq->where('break_work_pattern_cd', "like", "%" . $keyword . "%");
                    $sq->orWhere('break_name', "like", "%" . $keyword . "%");
                });
            })->get();
        $timezones = TimeZone::where('detail_times', "like", "%" . $keyword . "%")->get();
        $allUsers = User::all();


        $user_id_for_schedule = User::where('name', 'like', "%" . $keyword . "%")
            ->orWhere('code', 'like', "%" . $keyword . "%")
            ->orWhere('email', 'like', "%" . $keyword . "%")
            ->first('id')?->id;
        $schedule = Schedule::with(['occupations', 'locations'])
            ->where(function ($q) use ($keyword, $user_id_for_schedule) {
                $q->whereJsonContains('user_id', (int)$user_id_for_schedule);
                $q->orWhereHas('occupations', function ($sq) use ($keyword) {
                    $sq->where('occupation_name', 'like', "%" . $keyword . "%");
                });
                $q->orWhereHas('locations', function ($sq) use ($keyword) {
                    $sq->where('location_name', 'like', "%" . $keyword . "%");
                });
            })->get();
        $work_location = WorkLocation::where('location_name', "like", "%" . $keyword . "%")
            ->orWhere('location_address', "like", "%" . $keyword . "%")->get();
        $break_times = BreakTime::with('organization')
            ->where(function ($q) use ($keyword) {
                $q->orWhere('break_name', 'LIKE', '%' . $keyword . '%');
                $q->orWhere('break_work_pattern_cd', 'LIKE', '%' . $keyword . '%');
                $q->orWhereHas('organization', function ($sq) use ($keyword) {
                    $sq->where('organization_name', 'LIKE', '%' . $keyword . '%');
                });
            })->get();
        $occupation = Occupation::where('occupation_name', 'like', '%' . $keyword . '%')->get();
        $work_content = WorkContent::with('occupation')
            ->where(function ($q) use ($keyword) {
                $q->orWhere('work_content_name', 'like', '%' . $keyword . '%');
                $q->orWhereHas('occupation', function ($sq) use ($keyword) {
                    $sq->where('occupation_name', 'like', '%' . $keyword . '%');
                });
            })->get();
        $attend_status = AttendStatus::where('attend_name', "like", '%' . $keyword . '%')->get();
        $support_company = SupportCompany::where('support_company_name', 'like', '%' . $keyword . '%')
            ->orWhere('support_company_person', 'LIKE', '%' . $keyword . '%')
            ->orWhere('support_company_email', 'LIKE', '%' . $keyword . '%')
            ->orWhere('support_company_tel', 'LIKE', '%' . $keyword . '%')
            ->orWhere('support_company_zipcode', 'LIKE', '%' . $keyword . '%')
            ->orWhere('support_company_address', 'LIKE', '%' . $keyword . '%')
            ->get();
        $supported_company = SupportedCompany::where('supported_company_name', 'like', '%' . $keyword . '%')
            ->orWhere('supported_company_person', 'LIKE', '%' . $keyword . '%')
            ->orWhere('supported_company_email', 'LIKE', '%' . $keyword . '%')
            ->orWhere('supported_company_tel', 'LIKE', '%' . $keyword . '%')
            ->orWhere('supported_company_zipcode', 'LIKE', '%' . $keyword . '%')
            ->orWhere('supported_company_address', 'LIKE', '%' . $keyword . '%')
            ->get();
        return Inertia::render("Admin/Filter/Index", compact(
            'keyword',
            'dakou_data',
            'dakou_child',
            'users',
            'timezones',
            'allUsers',
            'schedule',
            'work_location',
            'break_times',
            'occupation',
            'work_content',
            'attend_status',
            'support_company',
            'supported_company',
        ));
    }
}
