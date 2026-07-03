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

        $query = Activity::with('causer')->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('causer', function($qCauser) use ($search) {
                      $qCauser->where('name', 'like', "%{$search}%")
                              ->orWhere('username', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('event') && $request->input('event') !== 'all') {
            $query->where('event', $request->input('event'));
        }

        $activities = $query->paginate(20)->withQueryString();

        return Inertia::render('ActivityLogs/Index', [
            'activities' => $activities,
            'filters' => $request->only(['search', 'event']),
        ]);
    }
}
