<?php
use Illuminate\Support\Facades\Schedule;

Schedule::command('reminders:tasks')
    ->dailyAt('12:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('reminders:schedules')
    ->dailyAt('12:30')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground();