<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserData extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'work_pattern_code_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function break_times()
    {
        return $this->belongsTo(BreakTime::class, 'work_pattern_code_id', 'id');
    }
}
