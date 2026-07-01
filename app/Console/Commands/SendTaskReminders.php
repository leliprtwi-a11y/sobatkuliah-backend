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
    protected $description = 'Kirim notifikasi H-1 untuk tugas yang deadline besok (di jam yang sama dengan deadline)';

    public function handle(FcmService $fcm): int
    {
        // Waktu sekarang dalam WIB
        $nowWib = Carbon::now('Asia/Jakarta');

        // H-1: besok dalam WIB
        $tomorrowWib = $nowWib->copy()->addDay();

        // Cari task yang:
        // - deadline-nya besok (tanggal WIB)
        // - jam deadline == jam sekarang WIB (H:i)
        // - belum selesai
        $currentHour   = $nowWib->format('H');
        $currentMinute = $nowWib->format('i');

        $this->info("Cek reminders:tasks — sekarang {$nowWib->format('Y-m-d H:i')} WIB");

        // Ambil semua task deadline besok yang belum selesai
        $tomorrowDate = $tomorrowWib->toDateString(); // "2025-07-02"

        $tasks = Task::with('user')
            ->where('is_done', false)
            ->whereRaw("DATE(CONVERT_TZ(deadline, '+00:00', '+07:00')) = ?", [$tomorrowDate])
            ->get();

        $this->info("Task deadline besok ({$tomorrowDate}): {$tasks->count()} task");

        $sent = 0;
        foreach ($tasks as $task) {
            // Konversi deadline task ke WIB
            $deadlineWib = Carbon::parse($task->deadline)->setTimezone('Asia/Jakarta');

            // Kirim notifikasi hanya jika jam & menit deadline == sekarang
            // (scheduler jalan tiap menit, jadi ini akan match tepat 1x)
            if ($deadlineWib->format('H') !== $currentHour ||
                $deadlineWib->format('i') !== $currentMinute) {
                continue;
            }

            $user = $task->user;
            if (!$user || !$user->fcm_token) {
                $this->warn("  Skip task {$task->id}: tidak ada FCM token");
                continue;
            }

            $priorityLabel = match ($task->priority) {
                3       => '🔴 Tinggi',
                2       => '🟡 Sedang',
                default => '🟢 Rendah',
            };

            $deadlineFormatted = $deadlineWib->isoFormat('D MMM, HH:mm');

            $success = $fcm->sendToDevice(
                fcmToken: $user->fcm_token,
                title:    '⏰ Tugas Jatuh Tempo Besok!',
                body:     "{$task->title} — deadline {$deadlineFormatted} WIB ({$priorityLabel})",
                data:     [
                    'type'      => 'task_reminder',
                    'task_id'   => (string) $task->id,
                    'channelId' => 'sobatkuliah_task',
                ]
            );

            if ($success) {
                $sent++;
                $this->line("  ✅ Sent: {$task->title} → {$user->firebase_uid} (deadline {$deadlineFormatted})");
            } else {
                $this->error("  ❌ Failed: {$task->title}");
            }
        }

        $this->info("Selesai. Terkirim: {$sent} notifikasi task");
        Log::info("[reminders:tasks] {$nowWib->format('H:i')} WIB — Sent {$sent} task reminders");

        return Command::SUCCESS;
    }
}