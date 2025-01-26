<?php

namespace App\Service;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DbService
{
    public function check_and_create_db()
    {
        $defaultDatabase = Config::get('database.connections.mysql.database');
        Config::set('database.connections.mysql.database', 'information_schema');
        // Reconnect to apply the temporary database change
        DB::purge('mysql');
        DB::reconnect('mysql');
        $result = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$defaultDatabase]);
        if (empty($result)) {
            try {
                // Create the new database
                DB::statement("CREATE DATABASE `$defaultDatabase`");

                return "Database '{$defaultDatabase}' created successfully.";
            } catch (\Exception $e) {
                return 'Error creating database: ' . $e->getMessage();
            }
        }

        // Restore the default database
        Config::set('database.connections.mysql.database', $defaultDatabase);

        // Reconnect to apply the original database change
        DB::purge('mysql');
        DB::reconnect('mysql');
    }
}
