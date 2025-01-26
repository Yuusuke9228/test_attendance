<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DakouChild extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'dp_dakoku_id',
        'dp_occupation_id', //職種 occupations->id
        'dp_timezone_id', // 時間帯 TimeZone id
        'dp_support_flg', //応援区分 0,1,2
        'dp_support_company_id', // SupportCompany->id flg= 2
        'dp_supported_company_id', // SupportedCompany->id flg = 1
        'dp_nums_of_people', //応援人数入力 integer
        'dp_work_contens_id', //作業内容 WorkContent->id
        'dp_work_location_id', // 現場 WorkLocation->id
        'dp_workers_master', //作業責任者 User->id
        'dp_workers', //現場人員 User->id
        'dp_unique_counter' //ユニックの回数
    ];

    protected $date = ['deleted_at'];

    public function dakoku()
    {
        return $this->belongsTo(DakouData::class, 'dp_dakoku_id', 'id');
    }

    public function support_company()
    {
        return $this->belongsTo(SupportCompany::class,'dp_support_company_id');
    }

    public function supported_company()
    {
        return $this->belongsTo(SupportedCompany::class,'dp_supported_company_id');
    }

    public function occupation()
    {
        return $this->belongsTo(Occupation::class, 'dp_occupation_id');
    }

    public function work_content()
    {
        return $this->belongsTo(WorkContent::class,'dp_work_contens_id');
    }

    public function work_location()
    {
        return $this->hasOne(WorkLocation::class, 'id', 'dp_work_location_id');
    }

    public function worker_master()
    {
        return $this->belongsTo(User::class,'dp_workers_master');
    }

    public function timezone()
    {
        return $this->belongsTo(TimeZone::class,'dp_timezone_id');
    }

}
