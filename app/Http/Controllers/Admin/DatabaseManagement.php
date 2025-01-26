<?php

namespace App\Http\Controllers\Admin;

use App\Events\DatabaseBackupEvent;
use App\Http\Controllers\Controller;
use App\Service\DbService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DatabaseManagement extends Controller
{
    public function index()
    {
        $backupPath = storage_path('app/public/backup');;
        $backupList = [];
        if (File::isDirectory($backupPath)) {
            $files = File::files($backupPath);
            foreach ($files as $key => $file) {
                $backupList[$key]['name'] = $file->getFilename();
                $backupList[$key]['size'] = filesize($file);
                $backupList[$key]['date'] = Carbon::parse($file->getCTime())->format('Y-m-d H:i:s');
            }
        }
        if (!empty($backupList)) {
            usort($backupList, function ($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
        }
        return Inertia::render("Admin/DBManage/Index", compact("backupList"));
    }
    public function backup()
    {
        try {
            ini_set('max_execution_time', 999999);
            $file_name = date('YmdHis') . '_db_dump.sql';
            if (!Storage::disk('public')->exists('backup')) {
                Storage::disk('public')->makeDirectory('backup', 0777, true);
            }
            $file_path = storage_path('app/public/backup/' . $file_name);
            $db_host = env('DB_HOST');
            $db_name = env('DB_DATABASE');
            $db_user = env('DB_USERNAME');
            $db_pass = env('DB_PASSWORD');
            $db_port = env('DB_PORT');

            // mysqldump 実行
            $output = [];
            $exit_code = 0;
            if (env('APP_ENV') == 'local') {
                $l_command = "D://xampp/mysql/bin/mysqldump -h{$db_host} -u{$db_user} -P {$db_port} -B {$db_name} > " . $file_path;
                exec($l_command, $output, $exit_code);
            } else {
                $s_command = "/usr/bin/mysqldump -h{$db_host} -u{$db_user} -p{$db_pass} -P {$db_port} -B {$db_name} > " . $file_path;
                exec($s_command, $output, $exit_code);
            }
            // $exit_code == 0 desribe success of exec running
            if ($exit_code == 0) {
                $check_url = route('admin.dbmanage.index');
                $date = date('Y年n月j日 H時i分');
                event(new DatabaseBackupEvent($date));
                return response()->json(['path' => 'backup/' . $file_name]);
            } else {
                return response()->json(['error' => 'DB migration failed']);
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function restore(Request $request)
    {
        try {
            if ($request->input('path')) {
                $file_path = storage_path('app/public/backup/' . $request->input('path'));
            } else if ($request->file('file')) {
                $file = $request->file('file');
                $file_name = date('YmdHis') . '_db_dump.sql';
                $backuped_file = $file->storeAs('backup', $file_name, 'public');
                $file_path = storage_path('app/public/' . $backuped_file);
            }

            if ($file_path) {
                $this->restore_method($file_path);
            }
            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function restore_method($path = null)
    {
        if (!$path) {
            $backupPath = storage_path('app/public/backup');;
            $files = File::files($backupPath);
            $latest_file = $files[count($files) - 1]->getFilename();
            $file_path = $backupPath . '/' . $latest_file;
        } else {
            $file_path = $path;
        }

        ini_set('max_execution_time', 999999);
        ini_set('upload_max_filesize', '4000M');
        ini_set('post_max_size', '4000M');

        $db_host = env('DB_HOST');
        $db_name = env('DB_DATABASE');
        $db_user = env('DB_USERNAME');
        $db_pass = env('DB_PASSWORD');
        $db_port = env('DB_PORT', 3306);

        $db_service = new DbService();

        // if database not created, 
        $db_service->check_and_create_db();

        $output = [];
        $exit_code = 0;
        if (env('APP_ENV') == 'local') {
            $l_command = "D://xampp/mysql/bin/mysql -h{$db_host} -u{$db_user} -P {$db_port} -B {$db_name} < " . $file_path;
            exec($l_command, $output, $exit_code);
            if ($exit_code !== 0) {
                return 1;
            }

            return 0;
        } else {
            $s_command = "/usr/bin/mysql -h{$db_host} -u{$db_user} -p{$db_pass} -P {$db_port} -B {$db_name} < " . $file_path;
            exec($s_command, $output, $exit_code);
        }
    }
}
