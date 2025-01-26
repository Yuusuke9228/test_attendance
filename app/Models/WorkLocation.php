<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkLocation extends Model
{
    use HasFactory;
    protected $fillable = [
        'location_flag',
        'location_name',
        'location_address',
    ];

    public function dakou_child()
    {
        return $this->hasMany(DakouChild::class, 'dp_work_location_id',);
    }
}
