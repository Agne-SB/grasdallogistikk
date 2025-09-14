<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('mw:sync-orders')
    ->everyTwoMinutes()      // ← run every 2 minutes
    ->withoutOverlapping() 
    ->runInBackground()      
    ->timezone('Europe/Oslo');
