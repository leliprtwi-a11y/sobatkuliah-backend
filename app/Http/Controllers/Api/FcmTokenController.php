<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    public function store(Request $request)
    {
        $uid   = $request->input('firebase_uid');
        $token = $request->input('token');

        User::where('firebase_uid', $uid)
            ->update(['fcm_token' => $token]);

        return response()->json(['status' => 'ok']);
    }
}