<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Models\Event;

class PlatformDashboardController extends Controller
{
    public function index()
    {
        $totalOrganizations = Organization::count();
        $activeOrganizations = Organization::where('status', 'active')->count();
        $suspendedOrganizations = Organization::where('status', 'suspended')->count();
        $inactiveOrganizations = Organization::where('status', 'inactive')->count();

        // Scopes do not apply because platform admin is not a User model
        $totalUsers = User::count();
        $totalEvents = Event::count();

        $recentOrganizations = Organization::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $topOrganizations = Organization::withCount('events')
            ->orderBy('events_count', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_organizations'     => $totalOrganizations,
            'active_organizations'    => $activeOrganizations,
            'suspended_organizations' => $suspendedOrganizations,
            'inactive_organizations'  => $inactiveOrganizations,
            'total_users'             => $totalUsers,
            'total_events'            => $totalEvents,
            'recent_organizations'    => $recentOrganizations,
            'top_organizations'       => $topOrganizations,
        ]);
    }
}
