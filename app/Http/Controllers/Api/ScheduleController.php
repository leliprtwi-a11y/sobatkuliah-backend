<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function upsert(Request $request)
    {
        $uid = $request->input('firebase_uid');

        Schedule::updateOrCreate(
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

        return response()->json(['status' => 'ok']);
    }

    public function destroy(Request $request, string $id)
    {
        Schedule::where('id', $id)
                ->where('firebase_uid', $request->input('firebase_uid'))
                ->delete();

        return response()->json(['status' => 'ok']);
    }
}