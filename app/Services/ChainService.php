<?php

namespace App\Services;

use App\Models\Chain;
use App\Models\Project;
use Illuminate\Validation\ValidationException;

class ChainService
{
    public function addToChain(int $chainId, int $projectId, ?int $position = null): Chain
    {
        $chain = Chain::findOrFail($chainId);
        $project = Project::findOrFail($projectId);

        $currentChains = $project->chains;
        if ($currentChains->isNotEmpty()) {
            foreach ($currentChains as $currentChain) {
                if ($currentChain->id === $chainId) {
                    return $chain->load('projects');
                }
                $currentChain->removeProject($projectId);
                if ($currentChain->projects()->count() === 0) {
                    $currentChain->delete();
                }
            }
        }

        if (!$chain->addProject($projectId, $position)) {
            throw ValidationException::withMessages([
                'project_id' => 'Project already exists in this chain.',
            ]);
        }

        $project->update(['chain_id' => $chainId]);

        return $chain->load('projects');
    }
    public function removeFromChain(int $chainId, int $projectId): Chain
    {
        $chain = Chain::findOrFail($chainId);
        $project = Project::findOrFail($projectId);

        $chain->removeProject($projectId);

        $newChain = Chain::create([
            'name' => $project->name . ' (Standalone)',
            'created_by' => $project->created_by,
        ]);
        $newChain->addProject($projectId);

        $project->update(['chain_id' => $newChain->id]);

        if ($chain->projects()->count() === 0) {
            $chain->delete();
        }

        return $newChain->load('projects');
    }

    public function reorderChain(int $chainId, array $orderedIds): Chain
    {
        $chain = Chain::findOrFail($chainId);

        // Validate that all projects belong to this chain
        $existingIds = $chain->projects()->pluck('id')->toArray();
        if (array_diff($orderedIds, $existingIds) || array_diff($existingIds, $orderedIds)) {
            throw ValidationException::withMessages([
                'project_ids' => 'Invalid project IDs for this chain.'
            ]);
        }

        $chain->reorderProjects($orderedIds);

        return $chain->load('projects');
    }

    public function getChain(int $chainId): Chain
    {
        return Chain::with(['projects', 'projects.creator', 'projects.users'])
            ->findOrFail($chainId);
    }

    public function getProjectChain(int $projectId): ?Chain
    {
        $project = Project::findOrFail($projectId);
        return $project->activeChain();
    }
}