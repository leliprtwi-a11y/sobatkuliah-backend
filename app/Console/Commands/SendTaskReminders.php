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
    protected $description = 'Kirim notifikasi H-1 untuk tugas yang deadline besok';

    public function handle(FcmService $fcm): int
    {
        $tomorrow    = Carbon::tomorrow()->setTimezone('Asia/Jakarta');
        $startOfDay  = $tomorrow->copy()->startOfDay();
        $endOfDay    = $tomorrow->copy()->endOfDay();

        $this->info("Mencari tugas deadline: {$startOfDay} – {$endOfDay}");

        // Ambil semua tugas yang deadline besok & belum selesai,
        // beserta data user (untuk FCM token)
        $tasks = Task::with('user')
            ->whereBetween('deadline', [$startOfDay, $endOfDay])
            ->where('is_done', false)
            ->get();

        $this->info("Ditemukan {$tasks->count()} tugas");

        $sent = 0;
        foreach ($tasks as $task) {
            $user = $task->user;

            if (!$user || !$user->fcm_token) {
                $this->warn("  Skip task {$task->id}: user tidak punya FCM token");
                continue;
            }

            $priorityLabel = match ($task->priority) {
                3       => '🔴 Tinggi',
                2       => '🟡 Sedang',
                default => '🟢 Rendah',
            };

            $deadlineFormatted = Carbon::parse($task->deadline)
                ->setTimezone('Asia/Makassar')
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

            if ($success) {
                $sent++;
                $this->line("  ✅ Sent: {$task->title} → {$user->firebase_uid}");
            } else {
                $this->error("  ❌ Failed: {$task->title}");
            }
        }

        $this->info("Selesai. Berhasil kirim: {$sent}/{$tasks->count()}");
        Log::info("[reminders:tasks] Sent {$sent}/{$tasks->count()} reminders");

        return Command::SUCCESS;
    }
}