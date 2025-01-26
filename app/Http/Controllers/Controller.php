<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function oneSheetCheck($file)
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load($file);
        $sheetnames = $spreadsheet->getSheetNames();
        if (count($sheetnames) > 1) {
            return false;
        } else {
            return true;
        }
    }
    public function array_some($array, $callback)
    {
        return array_reduce($array, function ($carry, $item) use ($callback) {
            return $carry || $callback($item);
        }, false);
    }
}
