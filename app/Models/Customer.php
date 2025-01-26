<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_name',
        'customer_person',
        'customer_saluation',
        'customer_email',
        'customer_tel',
        'customer_fax',
        'customer_zip_code',
        'customer_address_1',
        'customer_address_2',
        'customer_memo',
    ];
}
