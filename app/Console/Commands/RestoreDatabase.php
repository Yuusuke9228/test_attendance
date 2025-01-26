<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\DatabaseManagement;
use Illuminate\Console\Command;

class RestoreDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'restore:db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore by latest backup file using terminal';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $controller = new DatabaseManagement();
        $controller->restore_method();
        $this->info('Database restored');
    }
}
