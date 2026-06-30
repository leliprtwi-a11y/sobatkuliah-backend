<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function upsert(Request $request)
    {
        $uid = $request->input('firebase_uid');

        Task::updateOrCreate(
            ['id' => $request->id],
            [
                'firebase_uid' => $uid,
                'course_id'    => $request->course_id,
                'title'        => $request->title,
                'description'  => $request->description,
                'deadline'     => $request->deadline,
                'priority'     => $request->priority ?? 2,
                'is_done'      => $request->is_done ?? false,
            ]
        );

        return response()->json(['status' => 'ok']);
    }

    public function destroy(Request $request, string $id)
    {
        Task::where('id', $id)
            ->where('firebase_uid', $request->input('firebase_uid'))
            ->delete();

        return response()->json(['status' => 'ok']);
    }
}