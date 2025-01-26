<?php

namespace App\Helper;

use App\Exports\AttendDataExport;
use App\Exports\MasterCsvExport;
use App\Exports\MasterExcelExport;
use Maatwebsite\Excel\Facades\Excel;
class ExcelExport
{
    public static function collectingData($data, $path)
    {
        return Excel::store(new AttendDataExport($data), $path, 'public');
    }
    public static function exportCsv($data, $heading, $path)
    {
        return Excel::store(new MasterCsvExport($data, $heading), $path, 'public', \Maatwebsite\Excel\Excel::CSV, [
            'csv_encoding' => 'UTF-8',
            'Content-Type' => 'text/csv',
        ]);
    }

    public static function exportExcel($data, $heading, $path)
    {
        return Excel::store(new MasterExcelExport($data, $heading), $path, 'public');
    }
}
