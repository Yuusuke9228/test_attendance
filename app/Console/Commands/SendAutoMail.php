<?php

namespace App\Console\Commands;

use App\Service\DateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\DakokuNotifyMail;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;

class SendAutoMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:mail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ユーザーの打刻が連続3日間実施されない場合の通知';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /**
         * 3営業日でお願いします 
         * 休日、土、日曜日は除く
         */
        $holidays       = Holiday::whereYear('holiday_date', date("Y"))->where('paid_holiday', 0)->where('holiday_flag', 1)->pluck('holiday_date')?->toArray();
        $i = 0;
        $today = Carbon::now();
        do {
            $last_date = $today->subDay();
            if (!in_array(date('w', strtotime($last_date)), [0, 6]) && !in_array(Carbon::parse($last_date)->format("Y-m-d"), $holidays ?? [])) {
                $i++;
            }
        } while ($i < 3);
        
        $data = User::with('dakou')->where('role', '!=', 1)->whereDoesntHave('dakou', function($query) use ($last_date) {
            $query->whereDate('target_date',">=", $last_date);
        })->get();

        $users_not_attend = $data->map(function($item) {
            $email = $item->email;
            $name  = $item->name;
            $dakou_data = $item->dakou->toArray();
            if(!empty($dakou_data)) {
                usort($dakou_data, function($a, $b) {
                    return strtotime($b['target_date']) - strtotime($a['target_date']);
                });
                $last_date = $dakou_data[0]['target_date'];
                $diff_days = Carbon::now()->diffInDays(Carbon::parse($last_date));
            } else {
                $last_date = null;
                $diff_days = '未定';
            }
            return [
                'email'     => $email,
                'name'      => $name,
                'last_date' => $last_date ? Carbon::parse($last_date)->format('Y年n月j日') : '未定',
                'diff_days' => $diff_days,
            ];
        });
        $admin_mail_list = User::where('role', 1)->pluck('email');
        foreach($users_not_attend as $key => $val) {
            foreach($admin_mail_list as $admin) {
                Mail::to($admin)->send(new DakokuNotifyMail($val['name'], $val['last_date'], $val['diff_days']));
            }
        }
    }
}
