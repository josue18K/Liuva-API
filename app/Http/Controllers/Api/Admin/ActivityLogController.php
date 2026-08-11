<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'accion' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = ActivityLog::query()
            ->with('user:id,name,email,role')
            ->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('accion')) {
            $query->where('accion', 'like', '%' . $request->query('accion') . '%');
        }

        $logs = $query->paginate($request->integer('per_page', 20));

        return response()->json($logs);
    }

    public function show(ActivityLog $activityLog): JsonResponse
    {
        $activityLog->load('user:id,name,email,role');

        return response()->json([
            'activity_log' => $activityLog,
        ]);
    }
}
