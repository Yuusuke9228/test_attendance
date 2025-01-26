<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MasterExcelExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $data;
    protected $heading;

    public function __construct($data, $heading)
    {
        $this->data = $data;
        $this->heading = $heading;
    }
    public function collection()
    {
        return $this->data;
    }
    public function headings(): array
    {
        return $this->heading;
    }
}
