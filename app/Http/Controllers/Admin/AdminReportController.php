<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectReport;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type', 'all');
        $status = $request->get('status', '');

        $userReports = collect();
        $projectReports = collect();

        if (in_array($type, ['all', 'user'])) {
            $query = Report::with(['reporter', 'reportedUser']);
            if ($status) {
                $query->where('status', $status);
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

        if (in_array($type, ['all', 'project'])) {
            $query = ProjectReport::with(['reporter', 'reportedProject']);
            if ($status) {
                $query->where('status', $status);
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

        $reports = $userReports->concat($projectReports)->sortByDesc('created_at');

        return view('admin.reports.index', compact('reports', 'type', 'status'));
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
