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
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'accion' => ['nullable', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = ActivityLog::query()
            ->with('user:id,name,email,role')
            ->latest();

        $query
            ->when(isset($validated['user_id']), fn ($query) => $query->where('user_id', $validated['user_id']))
            ->when(isset($validated['accion']), fn ($query) => $query->where('accion', 'like', '%'.$validated['accion'].'%'))
            ->when(isset($validated['modelo']), fn ($query) => $query->where('modelo', $validated['modelo']))
            ->when(isset($validated['desde']), fn ($query) => $query->whereDate('created_at', '>=', $validated['desde']))
            ->when(isset($validated['hasta']), fn ($query) => $query->whereDate('created_at', '<=', $validated['hasta']));

        $logs = $query->paginate($validated['per_page'] ?? 20)->withQueryString();

        return response()->json(['activity_logs' => $logs]);
    }

    public function show(ActivityLog $activityLog): JsonResponse
    {
        $activityLog->load('user:id,name,email,role');

        return response()->json([
            'activity_log' => $activityLog,
        ]);
    }
}
