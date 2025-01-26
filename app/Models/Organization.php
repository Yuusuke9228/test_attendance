<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;
    protected $fillable = [
        'organization_parent_name',
        'organization_code',
        'organization_name',
        'organization_zipcode',
        'organization_address',
        'organization_master_name',
        'organization_parent_id',
    ];

    public function break_times()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function parentOrg()
    {
        return $this->belongsTo(Organization::class, 'organization_parent_id', 'id');
    }
}
