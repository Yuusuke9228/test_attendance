<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportedCompany extends Model
{
    // 応援に行った先 Flag 1
    use HasFactory;
    protected $fillable = [
        'supported_company_name',
        'supported_company_person',
        'supported_company_email',
        'supported_company_tel',
        'supported_company_zipcode',
        'supported_company_address',
    ];

    public function dakoku_child()
    {
        return $this->hasMany(DakouChild::class,'dp_supported_company_id', 'id');
    }
}
