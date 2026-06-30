<?php
// app/Services/FcmService.php
namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private string $projectId;
    private string $credentialsPath;

    public function __construct()
    {
        $this->projectId       = config('services.firebase.project_id');
        $this->credentialsPath = base_path(config('services.firebase.credentials'));
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
                    'data'         => array_map('strval', $data),
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

            $response = Http::withToken($accessToken)
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
     * Generate OAuth2 access token dari service account JSON
     */
    private function getAccessToken(): string
    {
        $scopes      = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = new ServiceAccountCredentials(
            $scopes,
            $this->credentialsPath
        );

        $token = $credentials->fetchAuthToken();
        return $token['access_token'];
    }
}