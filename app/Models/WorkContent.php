<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkContent extends Model
{
    use HasFactory;
    protected $fillable = [
        'work_content_occp_id',
        'work_content_name',
    ];

    public function occupation()
    {
        return $this->belongsTo(Occupation::class, 'work_content_occp_id');
    }
}
