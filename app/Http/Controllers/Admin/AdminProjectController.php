<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Support\DateRange;
use Illuminate\Http\Request;

class AdminProjectController extends Controller
{
    private const PER_PAGE_OPTIONS = [15, 30, 50];

    private const SORT_OPTIONS = ['newest', 'oldest', 'name_asc', 'name_desc'];

    private const STATUS_OPTIONS = ['active', 'paused', 'completed'];

    private const VISIBILITY_OPTIONS = ['public', 'private'];

    public function index(Request $request)
    {
        $dateRange = DateRange::fromRequest($request);

        $query = Project::with('creator')->withTrashed();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->createdBetween($dateRange->from, $dateRange->to);

        $status = $request->get('status');
        if (in_array($status, self::STATUS_OPTIONS, true)) {
            $query->where('status', $status);
        }

        $visibility = $request->get('visibility');
        if (in_array($visibility, self::VISIBILITY_OPTIONS, true)) {
            $query->where('visibility', $visibility);
        }

        if ($request->has('trashed') && $request->trashed === 'only') {
            $query->onlyTrashed();
        }

        if ($owner = trim((string) $request->get('owner'))) {
            $query->whereHas('creator', function ($q) use ($owner) {
                $q->where('name', 'like', "%{$owner}%");
            });
        }

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

        $projects = $query->paginate($perPage)->appends(request()->query());

        return view('admin.projects.index', compact(
            'projects',
            'dateRange',
            'status',
            'visibility',
            'owner',
            'sort',
            'perPage',
        ));
    }

    public function show($id)
    {
        $project = Project::withTrashed()->with('creator')->findOrFail($id);

        return view('admin.projects.show', compact('project'));
    }

    public function destroy($id)
    {
        $project = Project::withTrashed()->findOrFail($id);
        $name = $project->name;
        $project->forceDelete();

        return redirect()->route('admin.projects.index')
            ->with('success', "Project \"{$name}\" has been permanently deleted.");
    }
}
