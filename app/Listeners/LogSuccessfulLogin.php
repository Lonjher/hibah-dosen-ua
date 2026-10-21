<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Spatie\Activitylog\Facades\Activity;

class LogSuccessfulLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        Activity::causedBy($event->user)
            ->withProperties(['ip' => request()->ip(), 'user_agent' => request()->userAgent()])
            ->log('login');
    }
}
