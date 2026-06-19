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

    @php
        $sortLink = function (string $column, string $ascValue, string $descValue) use ($sort) {
            $next = ($sort === $ascValue) ? $descValue : $ascValue;
            return request()->fullUrlWithQuery(['sort' => $next, 'page' => 1]);
        };
        $sortCaret = function (string $column, string $ascValue, string $descValue) use ($sort) {
            if ($sort === $ascValue) { return '▲'; }
            if ($sort === $descValue) { return '▼'; }
            return '';
        };
    @endphp

    <div class="card">
        <div class="card-header">
            @push('extra_filters')
                <select name="type" class="form-control form-control-sm mr-2 mb-2" data-auto-submit>
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All types</option>
                    <option value="user" {{ $type === 'user' ? 'selected' : '' }}>User reports</option>
                    <option value="project" {{ $type === 'project' ? 'selected' : '' }}>Project reports</option>
                </select>

                <select name="status" class="form-control form-control-sm mr-2 mb-2" data-auto-submit>
                    <option value="">All statuses</option>
                    <option value="open" {{ $status === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="reviewed" {{ $status === 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                    <option value="dismissed" {{ $status === 'dismissed' ? 'selected' : '' }}>Dismissed</option>
                </select>

                <div class="input-group input-group-sm mr-2 mb-2" style="max-width: 260px;">
                    <input type="text" name="search" class="form-control" placeholder="Search reason, details, reporter..."
                        value="{{ $search }}">
                </div>

                <select name="sort" class="form-control form-control-sm mr-2 mb-2" data-auto-submit>
                    <option value="newest"     {{ $sort === 'newest'     ? 'selected' : '' }}>Newest first</option>
                    <option value="oldest"     {{ $sort === 'oldest'     ? 'selected' : '' }}>Oldest first</option>
                    <option value="reason_asc" {{ $sort === 'reason_asc' ? 'selected' : '' }}>Reason A→Z</option>
                </select>

                <select name="per_page" class="form-control form-control-sm mr-2 mb-2" data-auto-submit title="Per page">
                    @foreach ([15, 30, 50] as $n)
                        <option value="{{ $n }}" {{ (int) $perPage === $n ? 'selected' : '' }}>{{ $n }} / page</option>
                    @endforeach
                </select>
            @endpush

            @include('admin.partials._filter-bar', ['resetRoute' => route('admin.reports.index')])
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
                        <th>
                            <a href="{{ $sortLink('date', 'oldest', 'newest') }}" class="text-dark">
                                Date {{ $sortCaret('date', 'oldest', 'newest') }}
                            </a>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($paginator as $report)
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

        @if ($paginator->hasPages())
            <div class="card-footer clearfix">
                {{ $paginator->links() }}
            </div>
        @endif
    </div>
@stop
