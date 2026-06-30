<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        // Only allow users with 'view activity logs' permission
        if (!auth()->user()->hasPermissionTo('view activity logs')) {
            abort(403, 'Unauthorized action.');
        }

        $activities = Activity::with('causer')->latest()->paginate(20);

        return Inertia::render('ActivityLogs/Index', [
            'activities' => $activities
        ]);
    }
}
