<?php

namespace App\Console\Commands;

use App\Models\AttendStatus;
use App\Models\Holiday;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoDakokuCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:dakoku';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '有給指定日（カレンダーの黄色）は全員の有給打刻を自動で登録してほしい';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): void
    {
        $today = date("Y-m-d");
        $paid_holidays = Holiday::whereDate('holiday_date', $today)->where('holiday_flag', true)->where('paid_holiday', true)->exists();
        if (!$paid_holidays) return;

        $users = User::with(['user_data.break_times'])->where('role', '!=', 1)->get();
        $admin = User::where('role', 1)->first()?->id;
        $attend_other_status = AttendStatus::where('attend_name', '有給休暇')->first()?->id;
        DB::beginTransaction();
        try {
            $insert_data = [];
            foreach ($users as $val) {
                $regular_start_time = $val?->user_data?->break_times?->break_start_time ?? "08:00:00";
                $insert_data[] = [
                    'target_date'     => date("Y-m-d"),
                    "dp_user"         => $val->id,
                    "dp_status"       => 2, // 退勤
                    "dp_syukkin_time" => $regular_start_time,
                    "dp_other_flg"    => $attend_other_status,
                    "dp_made_by"      => $admin,
                    "dp_memo"         => "全員自動登録",
                ];
            }
            DB::table('dakou_data')->insert($insert_data);
            DB::commit();
        } catch (Exception $e) {
            Log::error("auto:dakoku command errors:" . $e->getMessage());
        }
    }
}
