<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display the activity log page with paginated data.
     */
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('module'), fn($q) => $q->where('module', $request->module))
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('activity-logs.index', compact('logs'));
    }
}
