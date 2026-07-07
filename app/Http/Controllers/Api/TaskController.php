<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::where('firebase_uid', $request->firebase_uid)->get();
        return response()->json($tasks);
    }

    public function show(Request $request, string $id)
    {
        $task = Task::where('id', $id)
                     ->where('firebase_uid', $request->firebase_uid)
                     ->firstOrFail();
        return response()->json($task);
    }

    public function upsert(Request $request)
    {
        $uid = $request->firebase_uid;

        if (!$request->id) {
            return response()->json(['error' => 'id wajib diisi'], 422);
        }

        $courseExists = \App\Models\Course::where('id', $request->course_id)
                                       ->where('firebase_uid', $uid)
                                       ->exists();
        if (!$courseExists) {
            return response()->json(['error' => 'course tidak ditemukan'], 422);
        }

        $existing = Task::find($request->id);
        if ($existing && $existing->firebase_uid !== $uid) {
            return response()->json(['error' => 'tidak diizinkan'], 403);
        }

        // Kalau deadline berubah, reset penanda "sudah dinotif" supaya
        // reminder dihitung ulang untuk deadline yang baru.
        $reminderResetNeeded = $existing && (string) $existing->deadline !== (string) $request->deadline;

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
                'notify_time'  => $request->notify_time, // "07:00" atau null
                'reminder_sent_at' => $reminderResetNeeded ? null : ($existing->reminder_sent_at ?? null),
            ]
        );

        return response()->json($task);
    }

    public function destroy(Request $request, string $id)
    {
        Task::where('id', $id)
            ->where('firebase_uid', $request->firebase_uid)
            ->delete();

        return response()->json(['status' => 'ok']);
    }
}