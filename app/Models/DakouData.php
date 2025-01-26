<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DakouData extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'target_date', //日付
        'dp_user',
        'dp_type', // 1日、半日
        'dp_status', //打刻区分
        'dp_syukkin_time', //出勤時間
        'dp_taikin_time', //退勤時刻
        'dp_break_start_time', //休憩開始時刻
        'dp_break_end_time', //休憩終了時刻
        'dp_gaishutu_start_time', //外出開始時刻
        'dp_gaishutu_end_time', //外出開終了時刻
        'dp_ride_flg', //運転・同乗
        'dp_other_flg',
        'dp_memo',
        'dp_dakou_address',
        'dp_made_by',
    ];
    protected $hidden = ['deleted_at'];
    protected $date = ['deleted_at'];

    public function user()
    {
        return $this->belongsTo(User::class, 'dp_user', 'id');
    }
    public function attend_type()
    {
        return $this->belongsTo(AttendType::class, 'dp_status', 'id');
    }

    public function attend_status()
    {
        return $this->belongsTo(AttendStatus::class, 'dp_other_flg');
    }

    public function dakoku_children()
    {
        return $this->hasMany(DakouChild::class, 'dp_dakoku_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'dp_made_by');
    }
}
