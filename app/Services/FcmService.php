<?php
// app/Services/FcmService.php
namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FcmService
{
    private string $projectId;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id');
    }

    /**
     * Kirim notifikasi ke satu device via FCM HTTP v1 API
     */
    public function sendToDevice(
        string $fcmToken,
        string $title,
        string $body,
        array  $data = []
    ): bool {
        try {
            $accessToken = $this->getAccessToken();

            $payload = [
                'message' => [
                    'token'        => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    // PENTING: cast ke (object). Kalau $data kosong ([]),
                    // array_map('strval', []) tetap [] dan di-encode PHP
                    // sebagai JSON array []. FCM MEWAJIBKAN field 'data'
                    // berupa objek/map ({}), bukan array — request akan
                    // ditolak Google dengan error "Cannot bind a list to
                    // map for field 'data'" kalau tidak di-cast begini.
                    'data'         => (object) array_map('strval', $data),
                    'android'      => [
                        'priority'     => 'high',
                        'notification' => [
                            'channel_id'   => $data['channelId'] ?? 'sobatkuliah_task',
                            'sound'        => 'notification_sound',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound'           => 'notification_sound.aiff',
                                'content-available' => 1,
                            ],
                        ],
                    ],
                ],
            ];

            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                            ->post($url, $payload);

            if ($response->successful()) {
                Log::info("[FCM] Berhasil kirim ke: {$fcmToken}");
                return true;
            }

            Log::error("[FCM] Gagal kirim: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("[FCM] Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate OAuth2 access token dari service account JSON.
     *
     * PENTING: kredensial diambil dari env var FIREBASE_SERVICE_ACCOUNT_JSON
     * (isi JSON-nya langsung, BUKAN path ke file). Ini karena file
     * kredensial sengaja tidak disimpan di disk container (isinya
     * private key rahasia, tidak boleh ikut ter-commit ke Git). Kalau
     * suatu saat mau pindah ke penyimpanan file, pastikan file itu
     * benar-benar ter-deploy ke server dan path-nya valid.
     */
    private function getAccessToken(): string
    {
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

        $serviceAccountJson = config('services.firebase.service_account_json');
        if (!$serviceAccountJson) {
            throw new RuntimeException(
                'FIREBASE_SERVICE_ACCOUNT_JSON belum diset di environment variables.'
            );
        }

        $jsonKey = json_decode($serviceAccountJson, true);
        if (!is_array($jsonKey)) {
            throw new RuntimeException(
                'FIREBASE_SERVICE_ACCOUNT_JSON tidak valid (gagal di-parse sebagai JSON).'
            );
        }

        // ServiceAccountCredentials menerima array kredensial langsung,
        // tidak harus path file.
        $credentials = new ServiceAccountCredentials($scopes, $jsonKey);

        $token = $credentials->fetchAuthToken();
        return $token['access_token'];
    }
}