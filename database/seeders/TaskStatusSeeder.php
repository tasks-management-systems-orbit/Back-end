<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\TaskStatus;
use Illuminate\Database\Seeder;

class TaskStatusSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::all();

        $defaultStatuses = [
            ['name' => 'To Do', 'position' => 0],
            ['name' => 'In Progress', 'position' => 1],
            ['name' => 'Done', 'position' => 2],
        ];

        foreach ($projects as $project) {
            foreach ($defaultStatuses as $statusData) {
                TaskStatus::firstOrCreate(
                    [
                        'project_id' => $project->id,
                        'name' => $statusData['name'],
                    ],
                    array_merge($statusData, ['project_id' => $project->id])
                );
            }
        }
    }
}
