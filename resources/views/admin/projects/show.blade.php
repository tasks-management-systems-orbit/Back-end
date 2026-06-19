@extends('adminlte::page')

@section('title', 'Project Detail')

@section('content_header')
    <h1>Project Detail</h1>
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
                    <h3 class="card-title">{{ $project->name }}</h3>
                    @if ($project->trashed())
                        <span class="badge badge-danger float-right">Deleted</span>
                    @endif
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width:180px">ID</th>
                            <td>{{ $project->id }}</td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td>{{ $project->name }}</td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td>{{ $project->description ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Owner</th>
                            <td>{{ $project->creator?->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>{{ $project->status ?? 'active' }}</td>
                        </tr>
                        <tr>
                            <th>Visibility</th>
                            <td>{{ $project->visibility ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Start Date</th>
                            <td>{{ $project->start_date ? $project->start_date->format('Y-m-d') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>End Date</th>
                            <td>{{ $project->end_date ? $project->end_date->format('Y-m-d') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Created</th>
                            <td>{{ $project->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                        @if ($project->trashed())
                            <tr>
                                <th>Deleted At</th>
                                <td>{{ $project->deleted_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            @if ($project->trashed())
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Actions</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.projects.destroy', $project->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-block"
                                onclick="return confirm('Permanently delete this project? This action cannot be undone.')">
                                <i class="fas fa-trash"></i>
                                Force Delete Permanently
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Stats</h3>
                </div>
                <div class="card-body">
                    <p><strong>Members:</strong> {{ $project->users_count }}</p>
                    <p><strong>Tasks:</strong> {{ $project->tasks()->withTrashed()->count() }}</p>
                    <p><strong>Completed tasks:</strong> {{ $project->completed_tasks_count }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <a href="{{ url()->previous() ?: route('admin.projects.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Projects
            </a>
        </div>
    </div>
@stop
