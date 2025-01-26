<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportCompany extends Model
{
    
    // 応援来てもらった先 Flag 2
    use HasFactory;
    protected $fillable = [
        'support_company_name',
        'support_company_person',
        'support_company_email',
        'support_company_tel',
        'support_company_zipcode',
        'support_company_address',
    ];

    public function dakoku_child()
    {
        return $this->hasMany(DakouChild::class,'dp_support_company_id', 'id');
    }
}
