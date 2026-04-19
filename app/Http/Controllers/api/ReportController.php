<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\StoreReportRequest;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Submit a report against a user
     * POST /api/reports
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $reportedUser = User::findOrFail($request->reported_user_id);

            // Check if user already reported this person
            if ($user->hasReported($reportedUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already reported this user'
                ], 409);
            }

            $report = Report::create([
                'reporter_id' => $user->id,
                'reported_user_id' => $reportedUser->id,
                'reason' => $request->reason,
                'details' => $request->details,
            ]);

            // Auto increment report_count on profile
            if ($reportedUser->profile) {
                $reportedUser->profile->increment('report_count');
            }

            return response()->json([
                'success' => true,
                'message' => 'Report submitted successfully. Our team will review it.',
                'data' => [
                    'report_id' => $report->id,
                    'reported_user' => $reportedUser->name,
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

    /**
     * Get all reports (No pagination)
     * GET /api/reports
     */
    public function getAllReports(Request $request): JsonResponse
    {
        $userId = $request->get('user_id');

        $reports = Report::with(['reporter', 'reportedUser'])
            ->when($userId, function ($query, $userId) {
                $query->where('reported_user_id', $userId);
            })
            ->latest()
            ->get();

        // Group reports by reported user
        $groupedByUser = $reports->groupBy('reported_user_id')->map(function ($userReports, $userId) {
            $firstReport = $userReports->first();
            return [
                'user_id' => $userId,
                'user_name' => $firstReport->reportedUser->name,
                'user_email' => $firstReport->reportedUser->email,
                'total_reports' => $userReports->count(),
                'reasons' => $userReports->pluck('reason')->unique()->values(),
                'reports' => $userReports->map(function ($report) {
                    return [
                        'report_id' => $report->id,
                        'reporter_name' => $report->reporter->name,
                        'reporter_email' => $report->reporter->email,
                        'reason' => $report->reason,
                        'details' => $report->details,
                        'created_at' => $report->created_at->toISOString(),
                    ];
                }),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'all_reports' => $reports->map(function ($report) {
                    return [
                        'report_id' => $report->id,
                        'reporter' => [
                            'id' => $report->reporter->id,
                            'name' => $report->reporter->name,
                            'email' => $report->reporter->email,
                        ],
                        'reported_user' => [
                            'id' => $report->reportedUser->id,
                            'name' => $report->reportedUser->name,
                            'email' => $report->reportedUser->email,
                        ],
                        'reason' => $report->reason,
                        'details' => $report->details,
                        'created_at' => $report->created_at->toISOString(),
                    ];
                }),
                'grouped_by_user' => $groupedByUser,
                'summary' => [
                    'total_reports' => $reports->count(),
                    'total_users_reported' => $reports->unique('reported_user_id')->count(),
                    'total_reporters' => $reports->unique('reporter_id')->count(),
                ]
            ]
        ]);
    }

    /**
     * Get reports for a specific user (No pagination)
     * GET /api/reports/user/{userId}
     */
    public function getUserReports(Request $request, $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $reports = Report::with('reporter')
            ->where('reported_user_id', $userId)
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
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'total_reports' => $reports->count(),
                'reports' => $reports,
            ]
        ]);
    }
}
