<?php

namespace App\Http\Controllers\Admin;

use App\Helper\ExcelExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\{CsvFileRequest, UserRequest, UserUpdateRequest};
use App\Imports\UserImport;
use App\Models\{UserData, DakouData, User, UserManageHistory};
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class UserManageController extends Controller
{
    public function index(Request $req)
    {
        $page = $req->page;
        //search
        $filter = $req->all();

        $users = User::with('user_data.break_times.organization')
            ->when($req->id, function ($query) use ($req) {
                $query->where('id', $req->id);
            })->when($req->code, function ($query) use ($req) {
                $query->where('code',  'like', "%" . $req->code . "%");
            })->when($req->name, function ($query) use ($req) {
                $query->where('name', 'like', "%" . $req->name . "%");
            })->when($req->email, function ($query) use ($req) {
                $query->where('email', 'like', "%" . $req->email . "%");
            })->when($req->status, function ($query) use ($req) {
                $query->where('available', $req->status);
            })->when($req->organization, function ($query) use ($req) {
                $query->whereHas('user_data', function ($q) use ($req) {
                    $q->whereHas('break_times', function ($sub_quer) use ($req) {
                        $sub_quer->where('id', ($req->organization)['id']);
                    });
                });
            })->when($req->startDate, function ($query) use ($req) {
                    $query->whereDate('created_at', '>=', $req->startDate);
            })->when($req->endDate, function ($query) use ($req) {
                $query->whereDate('created_at', '<=', $req->endDate);
            })->when($req->manager, function ($query) use ($req) {
                $query->where('role', $req->manager);
            })
            ->orderBy('id', 'DESC')->paginate(50);
        return Inertia::render('Admin/Users/UserList', compact('users', 'filter'));
    }
    public function create()
    {
        return Inertia::render('Admin/Users/CreateUser');
    }
    public function checkingCode(Request $request)
    {
        if ($request->code) {
            $check = User::where('code', $request->code)->exists();
            return response()->json($check);
        }
    }
    public function store(UserRequest $request)
    {
        $user = User::create([
            'code' => $request->code,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => 2,
            'available' => $request->available,
        ]);
        if ($user->id) {
            UserData::create([
                'user_id' => $user->id,
                'work_pattern_code_id' => $request->workParentCode['id'] ?? null
            ]);
            UserManageHistory::create([
                'user_id' => $user->id,
                'creater_id' => auth()->user()->id,
                'updater_id' => auth()->user()->id,
            ]);
        }
        return redirect()->route('admin.users.index');
    }
    public function show(Request $request, $id)
    {
        if ($request->month) {
            $month = $request->month;
            $year = $request->year;
        } else {
            $year = date('Y');
            $month = date('m');
        }
        $user = User::with(['user_data.break_times.organization', 'update_history.creater', 'update_history.updater'])
            ->find($id);
        $dakoku = DakouData::with(['attend_type', 'dakoku_children.support_company', 'user'])
            ->where('dp_user', $id)
            ->whereYear('target_date', $year)
            ->whereMonth('target_date', $month)
            ->orderBy('id', 'DESC')->get();
        return Inertia::render('Admin/Users/UserDetail', compact('user', 'dakoku'));
    }
    public function edit(Request $request, $id)
    {
        $userInfo = User::with(['user_data.break_times.organization', 'update_history.creater'])->find($id);
        return Inertia::render('Admin/Users/UserEdit', compact('userInfo'));
    }
    public function update(UserUpdateRequest $request)
    {
        if ($request->password_updateable) {
            $request->validate([
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);
        }
        $id = $request->id;
        $update_arr = [
            'code' => $request->code,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => 2,
            'available' => $request->available,
        ];
        if (!$request->password_updateable) {
            Arr::forget($update_arr, 'password');
        }

        User::where('id', $id)->update($update_arr);
        if ($id) {
            UserData::updateOrCreate(
                ['user_id' => $id],
                [
                    'work_pattern_code_id' => $request->workParentCode['id'] ?? null
                ]
            );
            UserManageHistory::where('user_id', $id)->update(
                [
                    'updater_id' => auth()->user()->id,
                ]
            );
        }
        return redirect()->route('admin.users.index');
    }
    public function destroy(Request $request)
    {
        if ($request->id) {
            User::where('id', $request->id)->delete();
        }
    }
    public function export(Request $request)
    {
        try {
            $type             = $request->input('type');
            $file_name_format = "ユーザー管理_%d.%s";
            $file_name        = sprintf($file_name_format, date('YmdHis'), $type);
            $path             = 'master/' . $file_name;

            $csv_data   = User::with('user_data.break_times.organization')->get()->collect()->map(function ($item) {
                return [
                    'id'             => $item->id,
                    'code'           => "=\"$item->code\"",
                    'name'           => $item->name,
                    'email'          => $item->email,
                    'email_verified' => $item->email_verified_at,
                    'password'       => $item->password,
                    'role'           => $item->role,
                    'status'         => $item->status,
                    'available'      => $item->available,
                    'user_data_id'    => $item->user_data?->id,
                    'parent_code'    => $item->user_data?->work_pattern_code_id,
                    'created_at'     => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
                    'updated_at'     => Carbon::parse($item->updated_at)->format('Y-m-d H:i:s'),
                ];
            });
            $excel_data = User::with(["user_data.break_times", "user_data.break_times.organization"])->get()->collect()->map(function ($item) {
                $working_style = $item->user_data?->break_times?->break_work_pattern_cd;
                $working_style .= $item->user_data?->break_times?->organization?->organization_name;
                $working_style .= $item->user_data?->break_times?->break_name;
                return [
                    'id'             => $item->id,
                    'code'           => $item->code,
                    'name'           => $item->name,
                    'email'          => $item->email,
                    'available'      => $item->available  ? "YES" : "NO",
                    'working_style'  => $working_style,
                    'created_at'     => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
                    'updated_at'     => Carbon::parse($item->updated_at)->format('Y-m-d H:i:s'),
                ];
            });
            $csv_heading = ["id", "code", "name", "email", "email_verify", "password", "role", "status", "available", "user_data_id", "parent_code", "created_at", "updated_at"];
            $heading = [
                "ID",
                "ユーザーコード",
                "ユーザー名",
                "メールアドレス",
                "ログインユーザー設定",
                "勤務形態コード",
                "作成日時",
                "更新日時"
            ];

            if ($type == 'csv') {
                $status = ExcelExport::exportCsv($csv_data, $csv_heading, $path);
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
            Excel::import(new UserImport, $csv);
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
