@extends('adminlte::page')

@section('title', 'Reports')

@section('content_header')
    <h1>Reports</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <form method="GET" class="form-inline">
                <select name="type" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All types</option>
                    <option value="user" {{ $type === 'user' ? 'selected' : '' }}>User reports</option>
                    <option value="project" {{ $type === 'project' ? 'selected' : '' }}>Project reports</option>
                </select>

                <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <option value="open" {{ $status === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="reviewed" {{ $status === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                    <option value="dismissed" {{ $status === 'dismissed' ? 'selected' : '' }}>Dismissed</option>
                </select>

                <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-secondary">Reset</a>
            </form>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Reporter</th>
                        <th>Target</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            <td>{{ $report['id'] }}</td>
                            <td>
                                @if ($report['type'] === 'user')
                                    <span class="badge badge-info">User</span>
                                @else
                                    <span class="badge badge-warning">Project</span>
                                @endif
                            </td>
                            <td>{{ $report['reporter_name'] }}</td>
                            <td>{{ $report['target_name'] }}</td>
                            <td>{{ Str::limit($report['reason'], 40) }}</td>
                            <td>
                                @if ($report['status'] === 'open')
                                    <span class="badge badge-danger">Open</span>
                                @elseif ($report['status'] === 'reviewed')
                                    <span class="badge badge-success">Reviewed</span>
                                @else
                                    <span class="badge badge-secondary">Dismissed</span>
                                @endif
                            </td>
                            <td>{{ $report['created_at']->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('admin.reports.show', [$report['type'], $report['id']]) }}"
                                    class="btn btn-xs btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No reports found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
