<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectReport;
use App\Models\Report;
use App\Models\User;
use App\Support\DateRange;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $dateRange = DateRange::fromRequest($request);

        $totalUsers = $dateRange->apply(User::query())->count();
        $activeUsers = $dateRange->apply(User::query())->where('is_active', true)->count();
        $inactiveUsers = $dateRange->apply(User::query())->where('is_active', false)->count();
        $totalProjects = $dateRange->apply(Project::withTrashed())->count();
        $openReports = $dateRange->apply(Report::open())->count()
            + $dateRange->apply(ProjectReport::open())->count();

        return view('admin.dashboard', compact(
            'dateRange',
            'totalUsers',
            'activeUsers',
            'inactiveUsers',
            'totalProjects',
            'openReports'
        ));
    }
}
