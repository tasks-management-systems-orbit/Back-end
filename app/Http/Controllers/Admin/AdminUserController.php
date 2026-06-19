<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DateRange;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    private const PER_PAGE_OPTIONS = [15, 30, 50];

    private const SORT_OPTIONS = ['newest', 'oldest', 'name_asc', 'name_desc'];

    public function index(Request $request)
    {
        $dateRange = DateRange::fromRequest($request);

        $query = User::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $verified = $request->get('email_verified', 'all');
        if ($verified === 'yes') {
            $query->whereNotNull('email_verified_at');
        } elseif ($verified === 'no') {
            $query->whereNull('email_verified_at');
        }

        $query->createdBetween($dateRange->from, $dateRange->to);

        $sort = in_array($request->get('sort'), self::SORT_OPTIONS, true)
            ? $request->get('sort')
            : 'newest';

        match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'name_asc' => $query->orderBy('name', 'asc')->orderBy('id', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc')->orderBy('id', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $perPage = (int) $request->get('per_page', 15);
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = 15;
        }

        $users = $query->paginate($perPage)->appends(request()->query());

        return view('admin.users.index', compact(
            'users',
            'dateRange',
            'verified',
            'sort',
            'perPage',
        ));
    }

    public function show($id)
    {
        $user = User::with(['profile', 'projects', 'ownedProjects'])->findOrFail($id);

        return view('admin.users.show', compact('user'));
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.users.index')
            ->with('success', "User {$user->name} has been {$status}.");
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "User {$name} has been permanently deleted.");
    }
}
