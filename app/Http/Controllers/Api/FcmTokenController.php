<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string|max:255',
        ]);

        // PENTING: pakai firebase_uid hasil verifikasi token di
        // FirebaseAuthMiddleware (di-set via $request->merge di sana),
        // JANGAN pakai $request->input('firebase_uid') dari body.
        // Kalau pakai body, siapapun yang login bisa kirim uid orang lain
        // dan menimpa fcm_token milik user lain (IDOR).
        $uid   = $request->firebase_uid;
        $token = $request->input('token');

        $updated = User::where('firebase_uid', $uid)->update(['fcm_token' => $token]);

        return response()->json(['status' => 'ok', 'updated' => (bool) $updated]);
    }
}