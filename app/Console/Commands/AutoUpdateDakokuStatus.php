<?php

namespace App\Console\Commands;

use App\Models\AttendStatus;
use App\Models\AttendType;
use App\Models\DakouData;
use Illuminate\Console\Command;

class AutoUpdateDakokuStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '退勤時間が登録された打刻データの打刻区分を退勤に自動更新。退勤に変更';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $taikin_status_id = AttendType::where('attend_type_name', '退勤')->value('id');
        DakouData::where('dp_taikin_time', '!=', null)->where('dp_status', '!=',  $taikin_status_id)->update(['dp_status' => $taikin_status_id]);
        $this->info('Update Successfully.');
    }
}
