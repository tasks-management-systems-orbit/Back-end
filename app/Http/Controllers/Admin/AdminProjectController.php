<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class AdminProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with('creator')->withTrashed();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('trashed') && $request->trashed === 'only') {
            $query->onlyTrashed();
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.projects.index', compact('projects'));
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
