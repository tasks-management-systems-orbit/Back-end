@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    @php
        // Pre-compute each card's deep-link URL with the active date range plus
        // any extra query params specific to that card.
        $dateQs = $dateRange->toQueryString();
        $usersBase    = route('admin.users.index') . '?' . http_build_query($dateQs);
        $activeLink   = route('admin.users.index') . '?' . http_build_query(array_merge($dateQs, ['status' => 'active']));
        $inactiveLink = route('admin.users.index') . '?' . http_build_query(array_merge($dateQs, ['status' => 'inactive']));
        $projectsLink = route('admin.projects.index') . '?' . http_build_query($dateQs);
        $reportsLink  = route('admin.reports.index') . '?' . http_build_query(array_merge($dateQs, ['status' => 'open']));
    @endphp

    <div class="card">
        <div class="card-body py-2">
            @include('admin.partials._filter-bar', ['resetRoute' => route('admin.dashboard')])

            @if ($dateRange->isActive())
                <div class="mt-2">
                    <span class="badge badge-info">
                        Showing counts for <strong>{{ $dateRange->summary() }}</strong>
                    </span>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-link btn-sm p-0 ml-2">Clear</a>
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <a href="{{ $usersBase }}" class="text-decoration-none">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $totalUsers }}</h3>
                        <p>Total Users</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="small-box-footer">
                        View users <i class="fas fa-arrow-circle-right"></i>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-6">
            <a href="{{ $activeLink }}" class="text-decoration-none">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $activeUsers }}</h3>
                        <p>Active Users</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="small-box-footer">
                        View active <i class="fas fa-arrow-circle-right"></i>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-6">
            <a href="{{ $inactiveLink }}" class="text-decoration-none">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $inactiveUsers }}</h3>
                        <p>Inactive Users</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div class="small-box-footer">
                        View inactive <i class="fas fa-arrow-circle-right"></i>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-6">
            <a href="{{ $projectsLink }}" class="text-decoration-none">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $totalProjects }}</h3>
                        <p>Total Projects</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="small-box-footer">
                        View projects <i class="fas fa-arrow-circle-right"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <a href="{{ $reportsLink }}" class="text-decoration-none">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ $openReports }}</h3>
                        <p>Open Reports</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-flag"></i>
                    </div>
                    <div class="small-box-footer">
                        View open reports <i class="fas fa-arrow-circle-right"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>
@stop
