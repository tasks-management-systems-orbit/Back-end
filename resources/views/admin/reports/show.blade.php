@extends('adminlte::page')

@section('title', 'Report Detail')

@section('content_header')
    <h1>Report Detail</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Report #{{ $report->id }}
                        @if ($type === 'user')
                            <span class="badge badge-info">User Report</span>
                        @else
                            <span class="badge badge-warning">Project Report</span>
                        @endif
                    </h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width:180px">Reporter</th>
                            <td>{{ $report->reporter?->name ?? 'Deleted User' }}
                                ({{ $report->reporter?->email ?? 'N/A' }})</td>
                        </tr>
                        <tr>
                            <th>Reason</th>
                            <td>{{ $report->reason }}</td>
                        </tr>
                        <tr>
                            <th>Details</th>
                            <td>{{ $report->details ?? 'No additional details' }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if ($report->status === 'open')
                                    <span class="badge badge-danger">Open</span>
                                @elseif ($report->status === 'reviewed')
                                    <span class="badge badge-success">Reviewed</span>
                                @else
                                    <span class="badge badge-secondary">Dismissed</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Reported</th>
                            <td>{{ $report->created_at->format('Y-m-d H:i') }}</td>
                        </tr>

                        @if ($type === 'user')
                            <tr>
                                <th>Reported User</th>
                                <td>
                                    @if ($report->reportedUser)
                                        {{ $report->reportedUser->name }}
                                        ({{ $report->reportedUser->email }})
                                        @if (!$report->reportedUser->is_active)
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    @else
                                        <span class="badge badge-secondary">Deleted</span>
                                    @endif
                                </td>
                            </tr>
                        @else
                            <tr>
                                <th>Reported Project</th>
                                <td>
                                    @if ($report->reportedProject)
                                        {{ $report->reportedProject->name }}
                                        @if ($report->reportedProject->trashed())
                                            <span class="badge badge-danger">Deleted (soft)</span>
                                        @endif
                                    @else
                                        <span class="badge badge-secondary">Deleted</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            @if ($report->status === 'open')
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Actions</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.reports.dismiss', [$type, $report->id]) }}" method="POST" class="mb-2">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-secondary btn-block"
                                onclick="return confirm('Dismiss this report?')">
                                <i class="fas fa-times"></i>
                                Dismiss Report
                            </button>
                        </form>

                        <form action="{{ route('admin.reports.content.delete', [$type, $report->id]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-block"
                                onclick="return confirm('Delete the reported content permanently? This cannot be undone.')">
                                <i class="fas fa-trash"></i>
                                Delete Violating Content
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Info</h3>
                    </div>
                    <div class="card-body">
                        <p>This report has been
                            <strong>{{ $report->status === 'reviewed' ? 'reviewed (content deleted)' : 'dismissed' }}</strong>.
                        </p>
                        <p>No further actions available.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </a>
        </div>
    </div>
@stop
