<?php

namespace App\Rules;

use App\Models\DakouData;
use Illuminate\Contracts\Validation\Rule;

class TaikinTimeRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public $id;
    public function __construct($id)
    {
        $this->id= $id;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $start_time = DakouData::find($this->id)?->dp_syukkin_time;
        return strtotime($start_time) < strtotime($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return '退勤時刻は、出勤時間より後の時間を指定してください。.';
    }
}
