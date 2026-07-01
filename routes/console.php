<?php
use Illuminate\Support\Facades\Schedule;

// Tugas: cek TIAP MENIT karena jam kirimnya mengikuti jam deadline
// masing-masing task (bukan jam fixed)
Schedule::command('reminders:tasks')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Jadwal kuliah: fixed jam 21:00 WIB
Schedule::command('reminders:schedules')
    ->dailyAt('21:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->runInBackground();