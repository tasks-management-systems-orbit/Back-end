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

    <div class="card">
        <div class="card-header">
            <form method="GET" class="form-inline">
                <div class="input-group input-group-sm mr-2">
                    <input type="text" name="search" class="form-control" placeholder="Search projects..."
                        value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <select name="trashed" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">All projects</option>
                    <option value="only" {{ request('trashed') === 'only' ? 'selected' : '' }}>Trashed only</option>
                </select>

                <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-secondary">Reset</a>
            </form>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Owner</th>
                        <th>Status</th>
                        <th>Visibility</th>
                        <th>Created</th>
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
                            <td colspan="7" class="text-center">No projects found.</td>
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
