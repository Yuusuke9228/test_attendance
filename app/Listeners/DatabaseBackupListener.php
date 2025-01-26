<?php

namespace App\Listeners;

use App\Events\DatabaseBackupEvent;
use App\Mail\BackupNotifyMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class DatabaseBackupListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\DatabaseBackupEvent  $event
     * @return void
     */
    public function handle(DatabaseBackupEvent $event)
    {
        if (env('APP_ENV') == 'server') {
            $date = $event->date;
            $admin_mail_list = User::where('role', 1)->pluck('email');
            foreach ($admin_mail_list as $mail) {
                Mail::to($mail)->send(new BackupNotifyMail($date));
            }
        }
    }
}
