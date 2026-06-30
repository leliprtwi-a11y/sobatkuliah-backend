<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\FcmTokenController;

Route::middleware('firebase.auth')->group(function () {

    // Courses
    Route::get   ('/courses',       [CourseController::class, 'index']);
    Route::get   ('/courses/{id}',  [CourseController::class, 'show']);
    Route::post  ('/courses/{id?}', [CourseController::class, 'upsert']);
    Route::delete('/courses/{id}',  [CourseController::class, 'destroy']);

    // Tasks
    Route::get   ('/tasks',         [TaskController::class, 'index']);
    Route::get   ('/tasks/{id}',    [TaskController::class, 'show']);
    Route::post  ('/tasks/{id?}',   [TaskController::class, 'upsert']);
    Route::delete('/tasks/{id}',    [TaskController::class, 'destroy']);

    // Schedules
    Route::get   ('/schedules',       [ScheduleController::class, 'index']);
    Route::get   ('/schedules/{id}',  [ScheduleController::class, 'show']);
    Route::post  ('/schedules/{id?}', [ScheduleController::class, 'upsert']);
    Route::delete('/schedules/{id}',  [ScheduleController::class, 'destroy']);

    // FCM Token
    Route::post('/fcm-token', [FcmTokenController::class, 'store']);
});