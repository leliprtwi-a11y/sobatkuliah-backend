<?php
namespace App\Console\Commands;

use App\Models\Task;
use App\Services\FcmService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTaskReminders extends Command
{
    protected $signature   = 'reminders:tasks';
    protected $description = 'Kirim notifikasi H-1 tepat di jam deadline tugas';

    public function handle(FcmService $fcm): int
    {
        $now = Carbon::now();

        // Cari task yang (deadline - 1 hari) jatuh persis di menit ini.
        // Ini otomatis menghormati jam deadline masing-masing task,
        // tidak lagi jam fixed.
        $targetStart = $now->copy()->addDay()->startOfMinute();
        $targetEnd   = $now->copy()->addDay()->endOfMinute();

        $tasks = Task::with('user')
            ->whereBetween('deadline', [$targetStart, $targetEnd])
            ->where('is_done', false)
            ->get();

        if ($tasks->isEmpty()) {
            return Command::SUCCESS; // sunyi, biar log tidak penuh (jalan tiap menit)
        }

        $sent = 0;
        foreach ($tasks as $task) {
            $user = $task->user;
            if (!$user || !$user->fcm_token) continue;

            $priorityLabel = match ($task->priority) {
                3       => '🔴 Tinggi',
                2       => '🟡 Sedang',
                default => '🟢 Rendah',
            };

            $deadlineFormatted = Carbon::parse($task->deadline)
                ->setTimezone('Asia/Jakarta')
                ->isoFormat('D MMM, HH:mm');

            $success = $fcm->sendToDevice(
                fcmToken: $user->fcm_token,
                title:    "⏰ Tugas Jatuh Tempo Besok!",
                body:     "{$task->title} — deadline {$deadlineFormatted} ({$priorityLabel})",
                data:     [
                    'type'      => 'task_reminder',
                    'task_id'   => $task->id,
                    'channelId' => 'sobatkuliah_task',
                ]
            );

            if ($success) $sent++;
        }

        Log::info("[reminders:tasks] Sent {$sent}/{$tasks->count()} reminders");
        return Command::SUCCESS;
    }
}