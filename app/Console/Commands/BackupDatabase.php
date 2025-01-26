<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\DatabaseManagement;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'DB backup!';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // return Command::SUCCESS;
        $controller = new DatabaseManagement();
        $controller->backup();
        $this->info('DB backuped');
    }
}
