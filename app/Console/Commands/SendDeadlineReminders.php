<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Task;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;

class SendDeadlineReminders extends Command
{
    protected $signature   = 'notifications:send';
    protected $description = 'Kirim push notification H-1 deadline task dan jadwal';

    public function handle(): void
    {
        $nowWib = Carbon::now('Asia/Jakarta');
        $this->info("Cek notifikasi: {$nowWib->toDateTimeString()} WIB");

        $this->sendTaskReminders($nowWib);
        $this->sendScheduleReminders($nowWib);
    }

    private function sendTaskReminders(Carbon $now): void
    {
        // Cari task yang deadlinenya besok dan notify_time-nya = jam sekarang
        $tomorrow = $now->copy()->addDay()->toDateString();
        $currentTime = $now->format('H:i');

        $tasks = Task::whereDate('deadline', $tomorrow)
            ->whereNotNull('notify_time')
            ->where('notify_time', $currentTime)
            ->where('is_done', false)
            ->with('user')
            ->get();

        foreach ($tasks as $task) {
            $user = User::where('firebase_uid', $task->firebase_uid)->first();
            if (!$user || !$user->fcm_token) continue;

            $courseName = \App\Models\Course::find($task->course_id)?->name ?? 'Matkul';
            $deadlineStr = Carbon::parse($task->deadline)
                ->setTimezone('Asia/Jakarta')
                ->format('d M Y H:i');

            $this->sendFcm(
                token: $user->fcm_token,
                title: "⏰ Deadline besok: {$task->title}",
                body:  "{$courseName} — deadline {$deadlineStr} WIB",
                data:  ['type' => 'task_reminder', 'task_id' => $task->id],
            );

            $this->info("Terkirim ke {$task->firebase_uid}: {$task->title}");
        }
    }

    private function sendScheduleReminders(Carbon $now): void
    {
        // Jadwal dikirim H-1 jam 21:00 WIB
        if ($now->format('H:i') !== '21:00') return;

        // Hitung hari besok (1=Senin...7=Minggu, Carbon: 1=Senin...0=Minggu)
        $tomorrowDow = $now->copy()->addDay()->isoWeekday(); // 1-7

        $schedules = Schedule::where('day_of_week', $tomorrowDow)
            ->with('user')
            ->get();

        // Kirim per user (satu notifikasi untuk semua jadwal besok)
        $grouped = $schedules->groupBy('firebase_uid');

        foreach ($grouped as $uid => $userSchedules) {
            $user = User::where('firebase_uid', $uid)->first();
            if (!$user || !$user->fcm_token) continue;

            $count = $userSchedules->count();
            $firstSchedule = $userSchedules->first();
            $courseName = \App\Models\Course::find($firstSchedule->course_id)?->name ?? 'Kuliah';

            $body = $count === 1
                ? "{$courseName} jam {$firstSchedule->start_time} — {$firstSchedule->room}"
                : "{$count} jadwal kuliah menanti besok";

            $this->sendFcm(
                token: $user->fcm_token,
                title: "📅 Jadwal kuliah besok",
                body:  $body,
                data:  ['type' => 'schedule_reminder'],
            );

            $this->info("Jadwal terkirim ke {$uid}");
        }
    }

    private function sendFcm(
        string $token,
        string $title,
        string $body,
        array  $data = []
    ): void {
        try {
            $serviceAccountJson = config('services.firebase.service_account_json');
            if (!$serviceAccountJson) {
                Log::error('[FCM] FIREBASE_SERVICE_ACCOUNT_JSON belum diset');
                return;
            }

            $accessToken = $this->getAccessToken($serviceAccountJson);
            $projectId   = json_decode($serviceAccountJson, true)['project_id'];

            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token'        => $token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data'         => array_map('strval', $data),
                        'android'      => [
                            'notification' => ['channel_id' => 'sobatkuliah_task'],
                            'priority'     => 'high',
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('[FCM] Gagal kirim: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('[FCM] Exception: ' . $e->getMessage());
        }
    }

    private function getAccessToken(string $serviceAccountJson): string
    {
        $credentials = json_decode($serviceAccountJson, true);

        $now = time();
        $payload = [
            'iss'   => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ];

        // Buat JWT manual tanpa library tambahan
        $header  = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode($payload));
        $header  = rtrim(strtr($header, '+/', '-_'), '=');
        $payload = rtrim(strtr($payload, '+/', '-_'), '=');

        $signingInput = "{$header}.{$payload}";
        $privateKey   = $credentials['private_key'];

        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $jwt = "{$signingInput}.{$signature}";

        // Tukar JWT dengan access token Google
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        return $response->json('access_token');
    }
}