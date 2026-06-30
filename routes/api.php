<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\FcmTokenController;

Route::middleware('firebase.auth')->group(function () {

    // Courses
    Route::post  ('/courses/{id?}', [CourseController::class,  'upsert']);
    Route::delete('/courses/{id}',  [CourseController::class,  'destroy']);

    // Tasks
    Route::post  ('/tasks/{id?}',   [TaskController::class,    'upsert']);
    Route::delete('/tasks/{id}',    [TaskController::class,    'destroy']);

    // Schedules
    Route::post  ('/schedules/{id?}', [ScheduleController::class, 'upsert']);
    Route::delete('/schedules/{id}',  [ScheduleController::class, 'destroy']);

    // FCM Token
    Route::post('/fcm-token', [FcmTokenController::class, 'store']);
});