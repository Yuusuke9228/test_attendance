<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;
    protected $fillable = [
        'break_work_pattern_cd',
        'break_start_time',
        'break_end_time',
        'break_organization',
        'break_name',
        'break_start_time1',
        'break_end_time1',
        'break_start_time2',
        'break_end_time2',
        'break_start_time3',
        'break_end_time3',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'break_organization', 'id');
    }
    public function user_data()
    {
        return $this->hasMany(UserData::class, 'work_pattern_code_id', 'id');
    }
}
