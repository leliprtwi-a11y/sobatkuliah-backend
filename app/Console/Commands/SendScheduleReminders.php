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
    protected $description = 'Kirim notifikasi H-1 jadwal kuliah besok — dikirim jam 21:00 WIB';

    private array $dayNames = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
        4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
    ];

    public function handle(FcmService $fcm): int
    {
        $nowWib = Carbon::now('Asia/Jakarta');

        $this->info("Cek reminders:schedules — sekarang {$nowWib->format('Y-m-d H:i')} WIB");

        // Hanya jalan jam 21:00 WIB
        if ($nowWib->format('H:i') !== '21:00') {
            $this->line("  Bukan jam 21:00 WIB, skip.");
            return Command::SUCCESS;
        }

        // Hari besok (ISO: 1=Senin … 7=Minggu)
        $tomorrowDow  = $nowWib->copy()->addDay()->dayOfWeekIso;
        $tomorrowName = $this->dayNames[$tomorrowDow];

        $this->info("Mencari jadwal hari: {$tomorrowName} (dow={$tomorrowDow})");

        $schedules = Schedule::with(['user', 'course'])
            ->where('day_of_week', $tomorrowDow)
            ->get();

        $this->info("Ditemukan {$schedules->count()} jadwal");

        // Kirim satu notifikasi per user (ringkasan semua jadwal besok)
        $grouped = $schedules->groupBy('firebase_uid');
        $sent    = 0;

        foreach ($grouped as $uid => $userSchedules) {
            // Ambil user dari schedule pertama
            $user = $userSchedules->first()->user;
            if (!$user || !$user->fcm_token) {
                $this->warn("  Skip uid {$uid}: tidak ada FCM token");
                continue;
            }

            $count = $userSchedules->count();

            if ($count === 1) {
                $s          = $userSchedules->first();
                $courseName = $s->course?->name ?? 'Mata Kuliah';
                $body       = "{$courseName} — {$tomorrowName} {$s->start_time}–{$s->end_time} di {$s->room}";
            } else {
                // Buat ringkasan semua jadwal
                $lines = $userSchedules->map(function ($s) {
                    $courseName = $s->course?->name ?? 'Matkul';
                    return "{$courseName} {$s->start_time}";
                })->join(', ');
                $body = "{$count} jadwal besok: {$lines}";
            }

            $success = $fcm->sendToDevice(
                fcmToken: $user->fcm_token,
                title:    "📅 Jadwal Kuliah Besok ({$tomorrowName})",
                body:     $body,
                data:     [
                    'type'      => 'schedule_reminder',
                    'channelId' => 'sobatkuliah_schedule',
                ]
            );

            if ($success) {
                $sent++;
                $this->line("  ✅ Sent ke {$uid}: {$body}");
            } else {
                $this->error("  ❌ Failed ke {$uid}");
            }
        }

        $this->info("Selesai. Terkirim: {$sent} notifikasi jadwal");
        Log::info("[reminders:schedules] {$tomorrowName} — Sent {$sent} notifications");

        return Command::SUCCESS;
    }
}