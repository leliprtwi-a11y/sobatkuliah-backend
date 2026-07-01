<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::where('firebase_uid', $request->input('firebase_uid'))->get();
        return response()->json($tasks);
    }

    public function show(Request $request, string $id)
    {
        $task = Task::where('id', $id)
                     ->where('firebase_uid', $request->input('firebase_uid'))
                     ->firstOrFail();
        return response()->json($task);
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

        $task = Task::updateOrCreate(
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

        return response()->json($task);
    }

    public function destroy(Request $request, string $id)
    {
        Task::where('id', $id)
            ->where('firebase_uid', $request->input('firebase_uid'))
            ->delete();

        return response()->json(['status' => 'ok']);
    }
}