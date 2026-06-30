<?php
// app/Http/Middleware/FirebaseAuthMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\JWK;

class FirebaseAuthMiddleware
{
    // Google mempublikasikan public key (x509 cert) untuk verifikasi
    // ID token secara LOKAL (tanpa perlu network call ke Google tiap
    // request). Key di-cache 6 jam sesuai header Cache-Control Google.
    private const JWKS_URL = 'https://www.googleapis.com/service_accounts/v1/jwk/securetoken@system.gserviceaccount.com';
    private const JWKS_CACHE_KEY = 'firebase_jwks_keys';
    private const JWKS_CACHE_TTL = 21600; // 6 jam (detik)

    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $idToken = substr($authHeader, 7);

        try {
            $uid = $this->verifyFirebaseToken($idToken);
            if (!$uid) {
                return response()->json(['error' => 'Invalid token'], 401);
            }

            // PENTING: kolom 'email' di tabel users punya UNIQUE constraint.
            // Kalau dibiarkan kosong (''), user kedua/ketiga yang baru
            // register akan gagal insert karena dianggap duplikat dengan
            // user pertama yang email-nya juga ''. Pakai placeholder unik
            // berbasis firebase_uid supaya tidak pernah bentrok.
            $user = User::firstOrCreate(
                ['firebase_uid' => $uid],
                ['name' => 'User', 'email' => "{$uid}@placeholder.sobatkuliah.local"]
            );

            $request->merge(['firebase_uid' => $uid]);
            $request->setUserResolver(fn() => $user);
        } catch (\Exception $e) {
            Log::warning('[FirebaseAuth] Token verification failed: ' . $e->getMessage());
            return response()->json(['error' => 'Token verification failed'], 401);
        }

        return $next($request);
    }

    /**
     * Verifikasi Firebase ID Token secara LOKAL (kriptografis) memakai
     * public key Google, TANPA network call ke Google tiap request.
     * Jauh lebih cepat & lebih reliable dibanding REST API lama
     * (identitytoolkit v3) yang sudah deprecated dan rentan lambat/gagal
     * kalau koneksi internet laptop sedang tidak stabil.
     */
    private function verifyFirebaseToken(string $idToken): ?string
    {
        $projectId = config('services.firebase.project_id');
        if (!$projectId) {
            throw new \RuntimeException('services.firebase.project_id belum diset di .env / config/services.php');
        }

        $keys = $this->getGooglePublicKeys();

        // Decode & verifikasi signature + waktu (exp/iat) sekaligus.
        // Library ini otomatis cocokkan 'kid' di header token dengan
        // key yang sesuai dari daftar public key Google.
        $decoded = JWT::decode($idToken, JWK::parseKeySet($keys));

        // Validasi klaim wajib sesuai dokumentasi resmi Firebase:
        // https://firebase.google.com/docs/auth/admin/verify-id-tokens
        if (($decoded->aud ?? null) !== $projectId) {
            throw new \RuntimeException('aud klaim tidak cocok dengan project ID');
        }
        if (($decoded->iss ?? null) !== "https://securetoken.google.com/{$projectId}") {
            throw new \RuntimeException('iss klaim tidak valid');
        }
        if (empty($decoded->sub)) {
            throw new \RuntimeException('sub klaim kosong');
        }

        return $decoded->sub;
    }

    /**
     * Ambil & cache public key Google (JWKS). Hanya network call kalau
     * cache kosong/expired — bukan setiap request seperti sebelumnya.
     */
    private function getGooglePublicKeys(): array
    {
        return Cache::remember(self::JWKS_CACHE_KEY, self::JWKS_CACHE_TTL, function () {
            $response = Http::timeout(10)->get(self::JWKS_URL);

            if ($response->failed()) {
                throw new \RuntimeException('Gagal mengambil public key Google: ' . $response->status());
            }

            return $response->json();
        });
    }
}