<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectReport;
use App\Models\Report;
use App\Models\User;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminReportController extends Controller
{
    private const PER_PAGE_OPTIONS = [15, 30, 50];

    private const SORT_OPTIONS = ['newest', 'oldest', 'reason_asc'];

    public function index(Request $request)
    {
        $type = $request->get('type', 'all');
        $status = $request->get('status', '');
        $search = trim((string) $request->get('search', ''));
        $dateRange = DateRange::fromRequest($request);

        $userReports = collect();
        $projectReports = collect();

        if (in_array($type, ['all', 'user'], true)) {
            $query = Report::with(['reporter', 'reportedUser']);
            if ($status) {
                $query->where('status', $status);
            }
            $query->createdBetween($dateRange->from, $dateRange->to);
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('reason', 'like', "%{$search}%")
                        ->orWhere('details', 'like', "%{$search}%")
                        ->orWhereHas('reporter', fn ($r) => $r->where('name', 'like', "%{$search}%"));
                });
            }
            $userReports = $query->get()->map(fn ($r) => [
                'id' => $r->id,
                'type' => 'user',
                'reporter_name' => $r->reporter?->name ?? 'Deleted',
                'target_name' => $r->reportedUser?->name ?? 'Deleted',
                'target_id' => $r->reported_user_id,
                'reason' => $r->reason,
                'status' => $r->status,
                'created_at' => $r->created_at,
            ]);
        }

        if (in_array($type, ['all', 'project'], true)) {
            $query = ProjectReport::with(['reporter', 'reportedProject']);
            if ($status) {
                $query->where('status', $status);
            }
            $query->createdBetween($dateRange->from, $dateRange->to);
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('reason', 'like', "%{$search}%")
                        ->orWhere('details', 'like', "%{$search}%")
                        ->orWhereHas('reporter', fn ($r) => $r->where('name', 'like', "%{$search}%"));
                });
            }
            $projectReports = $query->get()->map(fn ($r) => [
                'id' => $r->id,
                'type' => 'project',
                'reporter_name' => $r->reporter?->name ?? 'Deleted',
                'target_name' => $r->reportedProject?->name ?? 'Deleted',
                'target_id' => $r->reported_project_id,
                'reason' => $r->reason,
                'status' => $r->status,
                'created_at' => $r->created_at,
            ]);
        }

        $sort = in_array($request->get('sort'), self::SORT_OPTIONS, true)
            ? $request->get('sort')
            : 'newest';

        $reports = $userReports->concat($projectReports);
        $reports = match ($sort) {
            'oldest' => $reports->sortBy('created_at')->values(),
            'reason_asc' => $reports->sortBy('reason')->values(),
            default => $reports->sortByDesc('created_at')->values(),
        };

        $perPage = (int) $request->get('per_page', 15);
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 15;
        }

        $page = (int) $request->get('page', 1);
        $page = max(1, $page);

        $total = $reports->count();
        $items = $reports->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        return view('admin.reports.index', compact(
            'paginator',
            'type',
            'status',
            'search',
            'dateRange',
            'sort',
            'perPage',
        ));
    }

    public function show($type, $id)
    {
        if ($type === 'user') {
            $report = Report::with(['reporter', 'reportedUser'])->findOrFail($id);

            return view('admin.reports.show', compact('report', 'type'));
        }

        $report = ProjectReport::with(['reporter', 'reportedProject'])->findOrFail($id);

        return view('admin.reports.show', compact('report', 'type'));
    }

    public function dismiss($type, $id)
    {
        if ($type === 'user') {
            $report = Report::findOrFail($id);
            $report->update(['status' => 'dismissed']);
        } else {
            $report = ProjectReport::findOrFail($id);
            $report->update(['status' => 'dismissed']);
        }

        return redirect()->route('admin.reports.index')
            ->with('success', 'Report has been dismissed.');
    }

    public function deleteContent($type, $id)
    {
        if ($type === 'user') {
            $report = Report::findOrFail($id);
            $user = User::find($report->reported_user_id);
            if ($user) {
                $user->delete();
            }
            $report->update(['status' => 'reviewed']);
        } else {
            $report = ProjectReport::findOrFail($id);
            $project = Project::withTrashed()->find($report->reported_project_id);
            if ($project) {
                $project->forceDelete();
            }
            $report->update(['status' => 'reviewed']);
        }

        return redirect()->route('admin.reports.index')
            ->with('success', 'Violating content has been deleted and report marked as reviewed.');
    }
}
