<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Register your scheduled commands
Schedule::command('jwt:cleanup-tokens --debug')->weekly()
    ->emailOutputTo('admin@example.com');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
