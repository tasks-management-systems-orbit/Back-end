@extends('adminlte::page')

@section('title', 'Projects')

@section('content_header')
    <h1>Projects</h1>
@stop

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            {{ session('success') }}
        </div>
    @endif

    @php
        $trashedOnly = request('trashed') === 'only';
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
                <div class="input-group input-group-sm mr-2 mb-2" style="max-width: 260px;">
                    <input type="text" name="search" class="form-control" placeholder="Search projects..."
                        value="{{ request('search') }}">
                </div>

                <select name="trashed" class="form-control form-control-sm mr-2 mb-2" data-auto-submit
                    title="Trashed projects are filtered by created_at, not deleted_at">
                    <option value="">All projects</option>
                    <option value="only" {{ $trashedOnly ? 'selected' : '' }}>Trashed only</option>
                </select>

                @if (! $trashedOnly)
                    <select name="status" class="form-control form-control-sm mr-2 mb-2" data-auto-submit>
                        <option value="">All statuses</option>
                        @foreach (['active', 'paused', 'completed'] as $s)
                            <option value="{{ $s }}" {{ ($status ?? '') === $s ? 'selected' : '' }}>
                                {{ ucfirst($s) }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <select name="visibility" class="form-control form-control-sm mr-2 mb-2" data-auto-submit>
                    <option value="">All visibilities</option>
                    @foreach (['public', 'private'] as $v)
                        <option value="{{ $v }}" {{ ($visibility ?? '') === $v ? 'selected' : '' }}>
                            {{ ucfirst($v) }}
                        </option>
                    @endforeach
                </select>

                <div class="input-group input-group-sm mr-2 mb-2" style="max-width: 220px;">
                    <input type="text" name="owner" class="form-control" placeholder="Filter by owner name…"
                        value="{{ $owner ?? '' }}" title="Match owner name (partial match)">
                </div>

                <select name="sort" class="form-control form-control-sm mr-2 mb-2" data-auto-submit>
                    <option value="newest"    {{ $sort === 'newest'    ? 'selected' : '' }}>Newest first</option>
                    <option value="oldest"    {{ $sort === 'oldest'    ? 'selected' : '' }}>Oldest first</option>
                    <option value="name_asc"  {{ $sort === 'name_asc'  ? 'selected' : '' }}>Name A→Z</option>
                    <option value="name_desc" {{ $sort === 'name_desc' ? 'selected' : '' }}>Name Z→A</option>
                </select>

                <select name="per_page" class="form-control form-control-sm mr-2 mb-2" data-auto-submit title="Per page">
                    @foreach ([15, 30, 50] as $n)
                        <option value="{{ $n }}" {{ (int) $perPage === $n ? 'selected' : '' }}>{{ $n }} / page</option>
                    @endforeach
                </select>
            @endpush

            @include('admin.partials._filter-bar', ['resetRoute' => route('admin.projects.index')])
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>
                            <a href="{{ $sortLink('name', 'name_asc', 'name_desc') }}" class="text-dark">
                                Name {{ $sortCaret('name', 'name_asc', 'name_desc') }}
                            </a>
                        </th>
                        <th>Owner</th>
                        <th>Status</th>
                        <th>Visibility</th>
                        <th>
                            <a href="{{ $sortLink('created', 'oldest', 'newest') }}" class="text-dark">
                                Created {{ $sortCaret('created', 'oldest', 'newest') }}
                            </a>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projects as $project)
                        <tr class="{{ $project->trashed() ? 'table-secondary' : '' }}">
                            <td>{{ $project->id }}</td>
                            <td>{{ $project->name }}</td>
                            <td>{{ $project->creator?->name ?? 'N/A' }}</td>
                            <td>
                                @if ($project->trashed())
                                    <span class="badge badge-danger">Deleted</span>
                                @else
                                    <span class="badge badge-{{ $project->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ $project->status ?? 'active' }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $project->visibility ?? 'N/A' }}</td>
                            <td>{{ $project->created_at->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('admin.projects.show', $project->id) }}" class="btn btn-xs btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if ($project->trashed())
                                    <form action="{{ route('admin.projects.destroy', $project->id) }}" method="POST"
                                        style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger"
                                            onclick="return confirm('Permanently delete this project? This cannot be undone.')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                @if ($trashedOnly)
                                    No trashed projects match the current filters.
                                @else
                                    No projects found.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($projects->hasPages())
            <div class="card-footer clearfix">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
@stop
