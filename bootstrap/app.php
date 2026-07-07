<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Schedule 'notifications:send' (SendDeadlineReminders) dihapus dari sini.
    // Command itu sudah tidak ada lagi (duplikat dari reminders:tasks +
    // reminders:schedules yang sekarang didaftarkan di routes/console.php).
    // Jangan tambahkan withSchedule() lagi di sini kalau schedule sudah
    // didefinisikan di routes/console.php — pakai satu tempat saja supaya
    // tidak ada schedule yang terdaftar dobel tanpa disadari lagi seperti
    // kasus ini.
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'firebase.auth' => \App\Http\Middleware\FirebaseAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();