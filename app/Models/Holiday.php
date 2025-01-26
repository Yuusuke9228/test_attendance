<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;
    protected $fillable = [
        'holiday_flag',
        'holiday_date',
        'paid_holiday',
    ];

    protected $casts = [
        "holiday_flag" => "boolean",
        "paid_holiday" => "boolean",
    ];
}
