<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $courses = Course::where('firebase_uid', $request->firebase_uid)->get();
        return response()->json($courses);
    }

    public function show(Request $request, string $id)
    {
        $course = Course::where('id', $id)
                         ->where('firebase_uid', $request->firebase_uid)
                         ->firstOrFail();
        return response()->json($course);
    }

    public function upsert(Request $request)
    {
        $uid = $request->firebase_uid;

        $existing = Course::find($request->id);
        if ($existing && $existing->firebase_uid !== $uid) {
            return response()->json(['error' => 'tidak diizinkan'], 403);
        }

        $course = Course::updateOrCreate(
            ['id' => $request->id],
            [
                'firebase_uid' => $uid,
                'name'         => $request->name,
                'lecturer'     => $request->lecturer,
                'color'        => $request->color ?? '#4CAF50',
            ]
        );

        return response()->json($course);
    }

    public function destroy(Request $request, string $id)
    {
        Course::where('id', $id)
              ->where('firebase_uid', $request->firebase_uid)
              ->delete();

        return response()->json(['status' => 'ok']);
    }
}