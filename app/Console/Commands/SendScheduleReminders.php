<?php
namespace App\Console\Commands;

use App\Models\Schedule;
use App\Models\User;
use App\Services\FcmService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduleReminders extends Command
{
    protected $signature   = 'reminders:schedules';
    protected $description = 'Kirim notifikasi H-1 untuk jadwal kuliah besok';

    // Mapping PHP weekday (1=Mon…7=Sun) ke label Indonesia
    private array $dayNames = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
        4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
    ];

    public function handle(FcmService $fcm): int
    {
        $tomorrowDayOfWeek = Carbon::tomorrow()->dayOfWeekIso; // 1–7
        $today = Carbon::today()->toDateString();

        $this->info("Mencari jadwal hari: {$this->dayNames[$tomorrowDayOfWeek]}");

        // Ambil jadwal untuk hari besok, TAPI skip yang sudah dikirim hari
        // ini (last_reminder_sent_date = hari ini). Ini mencegah user
        // menerima notifikasi jadwal berkali-kali kalau command ini
        // ke-trigger lebih dari sekali dalam hari yang sama.
        $schedules = Schedule::with(['user', 'course'])
            ->where('day_of_week', $tomorrowDayOfWeek)
            ->where(function ($q) use ($today) {
                $q->whereNull('last_reminder_sent_date')
                  ->orWhere('last_reminder_sent_date', '!=', $today);
            })
            ->get();

        $this->info("Ditemukan {$schedules->count()} jadwal yang perlu dinotif");

        $sent = 0;
        foreach ($schedules as $schedule) {
            $user = $schedule->user;

            if (!$user || !$user->fcm_token) {
                $this->warn("  Skip schedule {$schedule->id}: tidak ada FCM token");
                continue;
            }

            $courseName = $schedule->course?->name ?? 'Mata Kuliah';
            $dayLabel   = $this->dayNames[$schedule->day_of_week] ?? 'Besok';

            $success = $fcm->sendToDevice(
                fcmToken: $user->fcm_token,
                title:    "📚 Kuliah Besok!",
                body:     "{$courseName} — {$dayLabel} {$schedule->start_time}–{$schedule->end_time} di {$schedule->room}",
                data:     [
                    'type'        => 'schedule_reminder',
                    'schedule_id' => $schedule->id,
                    'channelId'   => 'sobatkuliah_schedule',
                ]
            );

            if ($success) {
                $sent++;
                $schedule->update(['last_reminder_sent_date' => $today]);
                $this->line("  ✅ Sent: {$courseName} → {$user->firebase_uid}");
            } else {
                $this->error("  ❌ Failed: {$courseName}");
            }
        }

        $this->info("Selesai. Berhasil kirim: {$sent}/{$schedules->count()}");
        Log::info("[reminders:schedules] Sent {$sent}/{$schedules->count()} reminders");

        return Command::SUCCESS;
    }
}