<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $schedules = Schedule::where('firebase_uid', $request->input('firebase_uid'))->get();
        return response()->json($schedules);
    }

    public function show(Request $request, string $id)
    {
        $schedule = Schedule::where('id', $id)
                             ->where('firebase_uid', $request->input('firebase_uid'))
                             ->firstOrFail();
        return response()->json($schedule);
    }

    public function upsert(Request $request)
    {
        $uid = $request->input('firebase_uid');

        if (!$request->id) {
            return response()->json(['error' => 'id wajib diisi'], 422);
        }

        // Pastikan course milik user ini ada di server dulu
        $courseExists = \App\Models\Course::where('id', $request->course_id)
                                       ->where('firebase_uid', $uid)
                                       ->exists();
        if (!$courseExists) {
            return response()->json(['error' => 'course tidak ditemukan'], 422);
        }

        $schedule = Schedule::updateOrCreate(
            ['id' => $request->id],
            [
                'firebase_uid' => $uid,
                'course_id'    => $request->course_id,
                'day_of_week'  => $request->day_of_week,
                'start_time'   => $request->start_time,
                'end_time'     => $request->end_time,
                'room'         => $request->room,
            ]
        );

        return response()->json($schedule);
    }

    public function destroy(Request $request, string $id)
    {
        Schedule::where('id', $id)
                ->where('firebase_uid', $request->input('firebase_uid'))
                ->delete();

        return response()->json(['status' => 'ok']);
    }
}