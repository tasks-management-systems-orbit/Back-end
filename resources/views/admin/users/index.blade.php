@extends('adminlte::page')

@section('title', 'Users')

@section('content_header')
    <h1>Users</h1>
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
            @php
                // Build sortable header URLs by toggling the current sort key.
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
            @push('extra_filters')
                <div class="input-group input-group-sm mr-2 mb-2" style="max-width: 260px;">
                    <input type="text" name="search" class="form-control" placeholder="Search name, email, username..."
                        value="{{ request('search') }}">
                </div>

                <select name="status" class="form-control form-control-sm mr-2 mb-2" data-auto-submit>
                    <option value="">All statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>

                <select name="email_verified" class="form-control form-control-sm mr-2 mb-2" data-auto-submit>
                    <option value="all" {{ ($verified ?? 'all') === 'all' ? 'selected' : '' }}>All verified</option>
                    <option value="yes" {{ ($verified ?? '') === 'yes' ? 'selected' : '' }}>Verified</option>
                    <option value="no"  {{ ($verified ?? '') === 'no'  ? 'selected' : '' }}>Not verified</option>
                </select>

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

            @include('admin.partials._filter-bar', ['resetRoute' => route('admin.users.index')])
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
                        <th>Email</th>
                        <th>Username</th>
                        <th>Status</th>
                        <th>Verified</th>
                        <th>
                            <a href="{{ $sortLink('registered', 'oldest', 'newest') }}" class="text-dark">
                                Registered {{ $sortCaret('registered', 'oldest', 'newest') }}
                            </a>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->username }}</td>
                            <td>
                                @if ($user->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @if ($user->email_verified_at)
                                    <span class="badge badge-success">Yes</span>
                                @else
                                    <span class="badge badge-warning">Not verified</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-xs btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form action="{{ route('admin.users.toggle', $user->id) }}" method="POST"
                                    style="display:inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-xs btn-{{ $user->is_active ? 'warning' : 'success' }}"
                                        onclick="return confirm('{{ $user->is_active ? 'Deactivate' : 'Activate' }} this user?')">
                                        <i class="fas fa-{{ $user->is_active ? 'ban' : 'check' }}"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                                    style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger"
                                        onclick="return confirm('Permanently delete this user? This cannot be undone.')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="card-footer clearfix">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@stop
