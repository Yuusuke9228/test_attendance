<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendStatus extends Model
{
    use HasFactory;
    // use SoftDeletes;
    protected $fillable = [
        'attend_name',
    ];

    // protected $dates = ['deleted_at'];
}
