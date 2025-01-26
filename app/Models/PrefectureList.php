<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrefectureList extends Model
{
    use HasFactory;
    protected $fillable = [
        'pref_name'
    ];
}
