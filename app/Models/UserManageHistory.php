<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserManageHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'creater_id',
        'updater_id',
    ];

    public function creater()
    {
        return $this->belongsTo(User::class, 'creater_id', 'id');
    }
    public function updater()
    {
        return $this->belongsTo(User::class, 'updater_id', 'id');
    }
}
