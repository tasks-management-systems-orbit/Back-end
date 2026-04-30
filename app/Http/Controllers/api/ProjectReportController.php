<?php

namespace app\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectReportRequest;
use App\Models\Project;
use App\Models\ProjectReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectReportController extends Controller
{
    public function store(StoreProjectReportRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $reportedProject = Project::findOrFail($request->reported_project_id);

            // Check if user already reported this project
            $existingReport = ProjectReport::where('reporter_id', $user->id)
                ->where('reported_project_id', $reportedProject->id)
                ->exists();

            if ($existingReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already reported this project'
                ], 409);
            }

            $report = ProjectReport::create([
                'reporter_id' => $user->id,
                'reported_project_id' => $reportedProject->id,
                'reason' => $request->reason,
                'details' => $request->details,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Project report submitted successfully. Our team will review it.',
                'data' => [
                    'report_id' => $report->id,
                    'reported_project' => $reportedProject->name,
                    'reason' => $report->reason,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting the report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // @phpstan-ignore-next-line
    public function getAllReports(Request $request): JsonResponse
    {
        $projectId = $request->input('project_id');

        $reports = ProjectReport::with(['reporter', 'reportedProject.creator'])
            ->when($projectId, function ($query, $projectId) {
                $query->where('reported_project_id', $projectId);
            })
            ->latest()
            ->get();

        // Group reports by reported project
        $groupedByProject = $reports->groupBy('reported_project_id')->map(function ($projectReports) {
            $firstReport = $projectReports->first();
            return [
                'project_id' => $firstReport->reported_project_id,
                'project_name' => $firstReport->reportedProject->name,
                'project_owner' => $firstReport->reportedProject->creator->name ?? 'Unknown',
                'total_reports' => $projectReports->count(),
                'reasons' => $projectReports->pluck('reason')->unique()->values(),
                'reports' => $projectReports->map(fn($report) => [
                    'report_id' => $report->id,
                    'reporter_name' => $report->reporter->name,
                    'reporter_email' => $report->reporter->email,
                    'reason' => $report->reason,
                    'details' => $report->details,
                    'created_at' => $report->created_at->toISOString(),
                ]),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'all_reports' => $reports->map(fn($report) => [
                    'report_id' => $report->id,
                    'reporter' => [
                        'id' => $report->reporter->id,
                        'name' => $report->reporter->name,
                        'email' => $report->reporter->email,
                    ],
                    'reported_project' => [
                        'id' => $report->reportedProject->id,
                        'name' => $report->reportedProject->name,
                        'owner' => $report->reportedProject->creator->name ?? 'Unknown',
                    ],
                    'reason' => $report->reason,
                    'details' => $report->details,
                    'created_at' => $report->created_at->toISOString(),
                ]),
                'grouped_by_project' => $groupedByProject,
                'summary' => [
                    'total_reports' => $reports->count(),
                    'total_projects_reported' => $reports->unique('reported_project_id')->count(),
                    'total_reporters' => $reports->unique('reporter_id')->count(),
                ],
            ]
        ]);
    }

    public function getProjectReports(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $reports = ProjectReport::with('reporter')
            ->where('reported_project_id', $projectId)
            ->latest()
            ->get()
            ->map(function ($report) {
                return [
                    'report_id' => $report->id,
                    'reporter' => [
                        'id' => $report->reporter->id,
                        'name' => $report->reporter->name,
                        'email' => $report->reporter->email,
                    ],
                    'reason' => $report->reason,
                    'details' => $report->details,
                    'created_at' => $report->created_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'owner' => $project->creator->name ?? 'Unknown',
                ],
                'total_reports' => $reports->count(),
                'reports' => $reports,
            ]
        ]);
    }
}
