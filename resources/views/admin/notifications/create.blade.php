@extends('adminlte::page')

@section('title', 'Send Notification')

@section('content_header')
    <h1>Send Notification</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Compose Notification</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.users.notify.store') }}" method="POST">
                        @csrf

                        @foreach ($users as $user)
                            <input type="hidden" name="user_ids[]" value="{{ $user->id }}">
                        @endforeach

                        {{-- Forward active users-list filters so the admin returns to the same view after sending. --}}
                        @foreach (($filters ?? []) as $key => $value)
                            @if (is_string($value) || is_numeric($value))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach

                        <div class="form-group">
                            <label>Recipients ({{ $users->count() }})</label>
                            <div class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                                @foreach ($users as $user)
                                    <span class="badge badge-secondary mr-1 mb-1">{{ $user->name }} ({{ $user->email }})</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="title">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title"
                                class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title') }}" placeholder="Notification title..." required>
                            @error('title')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="message">Message <span class="text-danger">*</span></label>
                            <textarea name="message" id="message" rows="5"
                                class="form-control @error('message') is-invalid @enderror"
                                placeholder="Enter notification message..." required>{{ old('message') }}</textarea>
                            @error('message')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="type">Type</label>
                            <select name="type" id="type" class="form-control @error('type') is-invalid @enderror">
                                <option value="info" {{ old('type') === 'info' ? 'selected' : '' }}>Info</option>
                                <option value="success" {{ old('type') === 'success' ? 'selected' : '' }}>Success</option>
                                <option value="warning" {{ old('type') === 'warning' ? 'selected' : '' }}>Warning</option>
                                <option value="error" {{ old('type') === 'error' ? 'selected' : '' }}>Error</option>
                                <option value="urgent" {{ old('type') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                            @error('type')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Notification
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
