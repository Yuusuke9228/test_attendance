<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_name',
        'company_kana',
        'company_zip_code',
        'company_tel01',
        'company_tel02',
        'company_tel03',
        'company_fax01',
        'company_fax02',
        'company_fax03',
        'company_pref',
        'company_addr01',
        'company_addr02',
        'company_bank_name',
        'company_bank_office_no',
        'company_bank_bank_account_type',
        'company_bank_bank_account_no',
        'company_month_closing_status',
        'company_month_closing_date'
    ];
}
