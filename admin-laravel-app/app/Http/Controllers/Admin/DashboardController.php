<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Organization;
use App\Models\Event;
use App\Models\News;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_organizations' => Organization::count(),
            'active_organizations' => Organization::where('status', 'active')->count(),
            'total_events' => Event::count(),
            'upcoming_events' => Event::where('start_date', '>', now())->count(),
            'total_news' => News::count(),
            'published_news' => News::where('status', 'published')->count(),
        ];

        $recent_users = User::latest('created')->take(5)->get();
        $recent_organizations = Organization::latest('created')->take(5)->get();
        $upcoming_events = Event::where('start_date', '>', now())
                                ->orderBy('start_date')
                                ->take(5)
                                ->get();

        return view('admin.dashboard', compact('stats', 'recent_users', 'recent_organizations', 'upcoming_events'));
    }

    /**
     * Get dashboard statistics via AJAX.
     */
    public function getStats()
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'active' => User::where('status', 'active')->count(),
                'new_this_month' => User::whereMonth('created', now()->month)->count(),
            ],
            'organizations' => [
                'total' => Organization::count(),
                'active' => Organization::where('status', 'active')->count(),
                'new_this_month' => Organization::whereMonth('created', now()->month)->count(),
            ],
            'events' => [
                'total' => Event::count(),
                'upcoming' => Event::where('start_date', '>', now())->count(),
                'this_month' => Event::whereMonth('start_date', now()->month)->count(),
            ],
            'news' => [
                'total' => News::count(),
                'published' => News::where('status', 'published')->count(),
                'this_month' => News::whereMonth('created', now()->month)->count(),
            ],
        ];

        return response()->json($stats);
    }
}