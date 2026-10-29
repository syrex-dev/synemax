<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sessions:generate --days=14')
    ->dailyAt('00:05')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

Schedule::command('bookings:expire-unpaid')
    ->everyMinute();

Schedule::command('sessions:resolve-quorum')
    ->everyMinute();
