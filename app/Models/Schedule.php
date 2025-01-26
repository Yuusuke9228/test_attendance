<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'location_id',
        'schedule_date',
        'schedule_start_time',
        'schedule_end_time',
        'occupation_id',
    ];

    public function occupations()
    {
        return $this->hasOne(Occupation::class,'id', 'occupation_id');
    }

    public function locations()
    {
        return $this->hasOne(WorkLocation::class, 'id', 'location_id');
    }

}
