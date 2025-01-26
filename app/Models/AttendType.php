<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendType extends Model
{
    use HasFactory;
    protected $fillable = [
        'attend_type_name'
    ];

    public function dakou()
    {
        return $this->hasMany(DakouData::class, 'dp_status', 'id');
    }
}
