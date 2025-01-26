<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Import controllers
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\Common\BasicDataController;
use App\Http\Controllers\Admin\{AttendanceManagementController, DatabaseManagement, UserManageController};
use App\Http\Controllers\Admin\BasicInfo\BasicInfoController;
use App\Http\Controllers\Admin\AttendanceRecord\AttendanceRecordManagementController;
use App\Http\Controllers\Admin\GlobalFilter\FilterController;
use App\Http\Controllers\Admin\Location\LocationCollectController;
use App\Http\Controllers\Admin\Master\{
    AttendanceController,
    TimeZoneController,
    LocationMasterManagementController,
    BreakTimeManagementController,
    OccupationController,
    WorkContentController,
    HolidaysController,
    AttendStatusController,
    SupportCompanyInfoController,
    SupportedCompanyInfoController,
    OrganizationController,
    CustomerController,
    ScheduleController
};
use App\Http\Controllers\Admin\Report\TableManagementController;
use App\Http\Controllers\Admin\Consitency\CheckController;
use App\Http\Controllers\User\UserDakokuController;
use App\Providers\RouteServiceProvider;

// info
Route::get('/phpinfo', function () {
    phpinfo();
});

// TEST
Route::get('/test', function () {
    echo 'TEST OK';;
});

Route::get('/', function () {
    return redirect()->intended(RouteServiceProvider::HOME);
});
Route::group(['middleware' => 'auth'], function () {
    Route::get('/redirect', RedirectController::class);
    // FEで基本テーブルのデータ取得
    Route::as('common.')->group(function() {
        Route::get('/fetch_all_data', [BasicDataController::class, 'fetch'])->name('all.data');
        // 打刻の整合性確認のため、
        Route::get('get_attend_data', [BasicDataController::class, 'confirm_attend'])->name('confirm.attend');
        // ユーザー名、打刻日、時間帯別に有効なデータを取得する
        Route::get('get_recommend_data', [BasicDataController::class, 'recommend_cond'])->name('get.recommend');
    });
    
    // 管理者権限
    Route::group(['namespace' => 'App\Http\Controllers\Admin', 'middleware' => 'auth.admin', 'prefix' => 'admin', 'as' => 'admin.'], function () {
        Route::get('/check_master_data', [BasicDataController::class, 'checkMaster'])->name('check.master');
        // Route::get('/', [DashboardController::class, 'index'])->name('top');

        // 出勤簿管理 HOME
        Route::group(['prefix' => 'dakoku_record', 'as' => 'record.'], function () {
            Route::group(['prefix' => 'special_date', 'as' => 'special.'], function () {
                Route::get('/', [AttendanceRecordManagementController::class, 'specialIndex'])->name('index');
            });
            Route::group(['prefix' => 'monthly', 'as' => 'monthly.'], function () {
                Route::get('/', [AttendanceRecordManagementController::class, 'monthIndex'])->name('index');
                Route::post('/export', [AttendanceRecordManagementController::class, 'exportExcel'])->name('export');
            });
            Route::post('/search', [AttendanceRecordManagementController::class, 'search'])->name('search');
        });

        // 勤怠管理
        Route::group(['prefix' => 'dakoku', 'as' => 'attendance.'], function () {
            Route::get('/', [AttendanceManagementController::class, 'index'])->name('index');
        });

        // 帳票管理
        Route::group(['prefix' => 'table_management', 'as' => 'report.'], function () {
            Route::get('/', [TableManagementController::class, 'index'])->name('index');
            Route::post('/export_attend_data', [TableManagementController::class, 'exportAttendData'])->name('monthly.data');
            Route::post('/export_daily_data', [TableManagementController::class, 'exportDailyData'])->name('daily.data');
            Route::post('/export_attend_book', [TableManagementController::class, 'exportAttendanceBook'])->name('attend.book');
            Route::post('/export_driver', [TableManagementController::class, 'exportDriverData'])->name('driver');
            Route::post('/export_attend_per_location', [TableManagementController::class, 'exportAttendPerLocationData'])->name('attend.per.location.data');
            Route::post('/export_manpower_data', [TableManagementController::class, 'exportManPowerData'])->name('manpower.data');
            Route::post('/export_support_flg_data', [TableManagementController::class, 'exportSupportFlgData'])->name('supportFlg.data');
            Route::post('/export_by_location', [TableManagementController::class, 'exportLocationMansData'])->name('location.data');
        });
        // 現場別集計
        Route::group(['prefix' => 'location_collect', 'as' => 'locationcollect.'], function() {
            Route::get('/', [LocationCollectController::class, 'index'])->name('index');
            Route::get('/collect', [LocationCollectController::class, 'collect'])->name('collect');
        });

        // 整合性確認
        Route::group(['prefix' => 'consistency_check', 'as' =>'consistency.'], function() {
            Route::get('/', [CheckController::class, 'index'])->name('index');
            Route::get('/fetch', [CheckController::class, 'fetch_data'])->name('fetch');
        });
        // ユーザー管理
        Route::group(['prefix' => 'users', 'as' => 'users.'], function () {
            Route::get('/', [UserManageController::class, 'index'])->name('index');
            Route::get('/create', [UserManageController::class, 'create'])->name('create');
            Route::post('/check', [UserManageController::class, 'checkingCode'])->name('check');
            Route::post('/store', [UserManageController::class, 'store'])->name('store');
            Route::get('/show/{id?}', [UserManageController::class, 'show'])->name('show');
            Route::get('/edit/{id}', [UserManageController::class, 'edit'])->name('edit');
            Route::patch('/update', [UserManageController::class, 'update'])->name('update');
            Route::delete('/destroy', [UserManageController::class, 'destroy'])->name('destroy');
            Route::post('/export', [UserManageController::class, 'export'])->name('export');
            Route::post('/import', [UserManageController::class, 'import'])->name('import');
        });


        // マスターデータ管理
        Route::group(['namespace' => 'Master', 'prefix' => 'master', 'as' => 'master.'], function () {
            // 勤怠管理
            Route::group(['prefix' => 'attendance', 'as' => 'attendance.'], function () {
                Route::get('/', [AttendanceController::class, 'index'])->name('index');
                Route::get('/create', [AttendanceController::class, 'create'])->name('create');
                Route::get('/check_exist', [AttendanceController::class, 'checkExistDate'])->name('check.exist');
                Route::post('/store', [AttendanceController::class, 'store'])->name('store');
                Route::get('/{id}', [AttendanceController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [AttendanceController::class, 'edit'])->name('edit');
                Route::put('/update', [AttendanceController::class, 'update'])->name('update');
                Route::post('/update_row', [AttendanceController::class, 'updateRow'])->name('update.row');
                Route::delete('/destroy', [AttendanceController::class, 'destroy'])->name('destroy');
                Route::post('/get_timeset', [AttendanceController::class, 'getTimeSet'])->name('timeset');
                Route::post('/export', [AttendanceController::class, 'export'])->name('export');
                Route::post('/import', [AttendanceController::class, 'import'])->name('import');
            });
            // 時間帯区分
            Route::group(['prefix' => 'timezone', 'as' => 'timezone.'], function () {
                Route::get('/', [TimeZoneController::class, 'index'])->name('index');
                Route::get('/create', [TimeZoneController::class, 'create'])->name('create');
                Route::post('/store', [TimeZoneController::class, 'store'])->name('store');
                Route::get('/{id}', [TimeZoneController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [TimeZoneController::class, 'edit'])->name('edit');
                Route::put('/update', [TimeZoneController::class, 'update'])->name('update');
                Route::delete('/destroy', [TimeZoneController::class, 'destroy'])->name('destroy');
                Route::post('/export', [TimeZoneController::class, 'export'])->name('export');
                Route::post('/import', [TimeZoneController::class, 'import'])->name('import');
            });
            // 作業予定管理
            Route::group(['prefix' => 'schedule', 'as' => 'schedule.'], function () {
                Route::get('/', [ScheduleController::class, 'index'])->name('index');
                Route::get('/create', [ScheduleController::class, 'create'])->name('create');
                Route::post('/store', [ScheduleController::class, 'store'])->name('store');
                Route::get('/{id}', [ScheduleController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [ScheduleController::class, 'edit'])->name('edit');
                Route::put('/update', [ScheduleController::class, 'update'])->name('update');
                Route::delete('/destroy', [ScheduleController::class, 'destroy'])->name('destroy');
                Route::post('/export', [ScheduleController::class, 'export'])->name('export');
                Route::post('/import', [ScheduleController::class, 'import'])->name('import');
            });
            // 現場管理 location_master
            Route::group(['prefix' => 'location_master', 'as' => 'location.'], function () {
                Route::get('/', [LocationMasterManagementController::class, 'index'])->name('index');
                Route::get('/create', [LocationMasterManagementController::class, 'create'])->name('create');
                Route::post('/store', [LocationMasterManagementController::class, 'store'])->name('store');
                Route::get('/{id}', [LocationMasterManagementController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [LocationMasterManagementController::class, 'edit'])->name('edit');
                Route::put('/update', [LocationMasterManagementController::class, 'update'])->name('update');
                Route::delete('/destroy', [LocationMasterManagementController::class, 'destroy'])->name('destroy');
                Route::post('/export', [LocationMasterManagementController::class, 'export'])->name('export');
                Route::post('/import', [LocationMasterManagementController::class, 'import'])->name('import');
            });
            // 休憩時間・勤務形態管理
            Route::group(['prefix' => 'break_time', 'as' => 'breaktime.'], function () {
                Route::get('/', [BreakTimeManagementController::class, 'index'])->name('index');
                Route::get('/create', [BreakTimeManagementController::class, 'create'])->name('create');
                Route::post('/store', [BreakTimeManagementController::class, 'store'])->name('store');
                Route::get('/{id}', [BreakTimeManagementController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [BreakTimeManagementController::class, 'edit'])->name('edit');
                Route::put('/update', [BreakTimeManagementController::class, 'update'])->name('update');
                Route::delete('/destroy', [BreakTimeManagementController::class, 'destroy'])->name('destroy');
                Route::post('/export', [BreakTimeManagementController::class, 'export'])->name('export');
                Route::post('/import', [BreakTimeManagementController::class, 'import'])->name('import');
            });
            // 職種管理
            Route::group(['prefix' => 'occupation', 'as' => 'occupation.'], function () {
                Route::get('/', [OccupationController::class, 'index'])->name('index');
                Route::get('/create', [OccupationController::class, 'create'])->name('create');
                Route::post('/store', [OccupationController::class, 'store'])->name('store');
                Route::get('/{id}', [OccupationController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [OccupationController::class, 'edit'])->name('edit');
                Route::put('/update', [OccupationController::class, 'update'])->name('update');
                Route::delete('/destroy', [OccupationController::class, 'destroy'])->name('destroy');
                Route::post('/export', [OccupationController::class, 'export'])->name('export');
                Route::post('/import', [OccupationController::class, 'import'])->name('import');
            });
            // 作業内容
            Route::group(['prefix' => 'work_contents', 'as' => 'work_contents.'], function () {
                Route::get('/', [WorkContentController::class, 'index'])->name('index');
                Route::get('/create', [WorkContentController::class, 'create'])->name('create');
                Route::post('/store', [WorkContentController::class, 'store'])->name('store');
                Route::get('/{id}', [WorkContentController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [WorkContentController::class, 'edit'])->name('edit');
                Route::put('/update', [WorkContentController::class, 'update'])->name('update');
                Route::delete('/destroy', [WorkContentController::class, 'destroy'])->name('destroy');
                Route::post('/export', [WorkContentController::class, 'export'])->name('export');
                Route::post('/import', [WorkContentController::class, 'import'])->name('import');
            });
            // 休日管理
            Route::group(['prefix' => 'holiday', 'as' => 'holiday.'], function () {
                Route::get('/', [HolidaysController::class, 'index'])->name('index');
                Route::get('/create', [HolidaysController::class, 'create'])->name('create');
                Route::get('/calendar', [HolidaysController::class, 'calendar'])->name('calendar');
                Route::post('/export_calendar', [HolidaysController::class, 'exportCalendar'])->name('exportCalendar');
                Route::get('/get_holidays', [HolidaysController::class, 'getHolidays'])->name('getholidays');
                Route::post('/store', [HolidaysController::class, 'store'])->name('store');
                Route::get('/{id}', [HolidaysController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [HolidaysController::class, 'edit'])->name('edit');
                Route::put('/update', [HolidaysController::class, 'update'])->name('update');
                Route::delete('/destroy', [HolidaysController::class, 'destroy'])->name('destroy');
                Route::delete('/destroy_by_year', [HolidaysController::class, 'destroyYear'])->name('destroy.year');
                Route::post('/export', [HolidaysController::class, 'export'])->name('export');
                Route::post('/import', [HolidaysController::class, 'import'])->name('import');
            });
            // 残業・早退・遅刻
            Route::group(['prefix' => 'attend_statuses', 'as' => 'attend_statuses.'], function () {
                Route::get('/', [AttendStatusController::class, 'index'])->name('index');
                Route::get('/create', [AttendStatusController::class, 'create'])->name('create');
                Route::post('/store', [AttendStatusController::class, 'store'])->name('store');
                Route::get('/{id}', [AttendStatusController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [AttendStatusController::class, 'edit'])->name('edit');
                Route::put('/update', [AttendStatusController::class, 'update'])->name('update');
                Route::delete('/destroy', [AttendStatusController::class, 'destroy'])->name('destroy');
                Route::post('/export', [AttendStatusController::class, 'export'])->name('export');
                Route::post('/import', [AttendStatusController::class, 'import'])->name('import');
            });
            // 応援に来てもらう会社
            Route::group(['prefix' => 'support_company_info', 'as' => 'support_company.'], function () {
                Route::get('/', [SupportCompanyInfoController::class, 'index'])->name('index');
                Route::get('/create', [SupportCompanyInfoController::class, 'create'])->name('create');
                Route::post('/store', [SupportCompanyInfoController::class, 'store'])->name('store');
                Route::get('/{id}', [SupportCompanyInfoController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [SupportCompanyInfoController::class, 'edit'])->name('edit');
                Route::put('/update', [SupportCompanyInfoController::class, 'update'])->name('update');
                Route::delete('/destroy', [SupportCompanyInfoController::class, 'destroy'])->name('destroy');
                Route::post('/export', [SupportCompanyInfoController::class, 'export'])->name('export');
                Route::post('/import', [SupportCompanyInfoController::class, 'import'])->name('import');
            });
            // 応援に行く先の会社
            Route::group(['prefix' => 'supported_company_info', 'as' => 'supported_company.'], function () {
                Route::get('/', [SupportedCompanyInfoController::class, 'index'])->name('index');
                Route::get('/create', [SupportedCompanyInfoController::class, 'create'])->name('create');
                Route::post('/store', [SupportedCompanyInfoController::class, 'store'])->name('store');
                Route::get('/{id}', [SupportedCompanyInfoController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [SupportedCompanyInfoController::class, 'edit'])->name('edit');
                Route::put('/update', [SupportedCompanyInfoController::class, 'update'])->name('update');
                Route::delete('/destroy', [SupportedCompanyInfoController::class, 'destroy'])->name('destroy');
                Route::post('/export', [SupportedCompanyInfoController::class, 'export'])->name('export');
                Route::post('/import', [SupportedCompanyInfoController::class, 'import'])->name('import');
            });
            // 組織
            Route::group(['prefix' => 'organization', 'as' => 'organization.'], function () {
                Route::get('/', [OrganizationController::class, 'index'])->name('index');
                Route::get('/create', [OrganizationController::class, 'create'])->name('create');
                Route::post('/store', [OrganizationController::class, 'store'])->name('store');
                Route::get('/{id}', [OrganizationController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [OrganizationController::class, 'edit'])->name('edit');
                Route::put('/update', [OrganizationController::class, 'update'])->name('update');
                Route::delete('/destroy', [OrganizationController::class, 'destroy'])->name('destroy');
                Route::post('/export', [OrganizationController::class, 'export'])->name('export');
                Route::post('/import', [OrganizationController::class, 'import'])->name('import');
            });
            Route::group(['prefix' => 'customer', 'as' => 'customer.'], function () {
                Route::get('/', [CustomerController::class, 'index'])->name('index');
                Route::get('/create', [CustomerController::class, 'create'])->name('create');
                Route::post('/store', [CustomerController::class, 'store'])->name('store');
                Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [CustomerController::class, 'edit'])->name('edit');
                Route::put('/update', [CustomerController::class, 'update'])->name('update');
                Route::delete('/destroy', [CustomerController::class, 'destroy'])->name('destroy');
            });
        });

        // システム基本設定
        Route::group(['namespace' => 'BasicInfo', 'prefix' => 'basic_info', 'as' => 'base.'], function () {
            Route::get('/', [BasicInfoController::class, 'index'])->name('index');
            Route::put('/update', [BasicInfoController::class, 'update'])->name('update');
        });

        // 統合検索
        Route::group(['namespace' => 'GlobalFilter', 'prefix' => 'filter', 'as' => 'filter.'], function () {
            Route::get('/', [FilterController::class, 'filter'])->name('index');
        });
        Route::group(['prefix' => 'db_manage',  'as' => 'dbmanage.'], function() {
            Route::get('/', [DatabaseManagement::class, 'index'])->name('index');
            Route::post('/backup', [DatabaseManagement::class, 'backup'])->name('backup');
            Route::post('/restore', [DatabaseManagement::class, 'restore'])->name('restore');
        });
    });

    // ユーザー権限
    Route::group(['namespace' => 'App\Http\Controllers\User'], function () {
        Route::group(['middleware' => 'auth.user', 'prefix' => 'user', 'as' => 'user.'], function () {
            Route::group(['prefix' => 'dakoku', 'as' => 'attendance.'], function () {
                // Today Dakoku Route
                Route::group(['prefix' => 'today', 'as' => 'today.'], function () {
                    Route::group(['middleware' => 'attendstatus:uncomplete'], function () {
                        Route::get('/syukkin', [UserDakokuController::class, 'syukkin'])->name('syukkin');
                        Route::post('/syukkin', [UserDakokuController::class, 'syukkinStore'])->name('syukkin.store');
                        Route::post('/taikin', [UserDakokuController::class, 'taikinStore'])->name('taikin.store');
                    });
                    Route::get('/complete', [UserDakokuController::class, 'dailyComplete'])->middleware('attendstatus:complete')->name('complete');
                });
                // All Data List
                Route::group(['prefix' => 'list', 'as' => 'list.'], function () {
                    Route::get('/', [UserDakokuController::class, 'index'])->name('index');
                    Route::get('/create&update/{date?}/{id?}', [UserDakokuController::class, 'create'])->name('create');
                    Route::post('/store', [UserDakokuController::class, 'store'])->name('store');
                    Route::get('/detail', [UserDakokuController::class, 'detail'])->name('detail');
                    Route::post('/destroy', [UserDakokuController::class, 'destroy'])->name('destroy');
                });
            });
        });
    });
});

// Not exist 404 Page, redirect RedirectController instead of it
Route::fallback(function() {
    return redirect('/redirect');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/download', function (Request $req) {
    session()->reflash();
    $undelete = $req->undelete;
    if($undelete) {
        return response()->download(public_path('storage/' . $req->file_path));
    } else {
        return response()->download(public_path('storage/' . $req->file_path))->deleteFileAfterSend(true);
    }
})->name('file_download');

// 認証ルートのインポート (Breeze)
require __DIR__ . '/auth.php';

