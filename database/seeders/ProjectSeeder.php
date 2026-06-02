<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $alaa = User::where('email', 'alaa.gbh0@gmail.com')->first();

        $projects = [
            [
                'name' => 'Website Redesign',
                'description' => 'Redesign the company website with modern UI/UX principles and improve mobile responsiveness.',
                'status' => 'active',
                'visibility' => 'public',
                'allow_join_requests' => true,
                'created_by' => $alaa->id,
            ],
            [
                'name' => 'Mobile App Development',
                'description' => 'Build a cross-platform mobile application for task management on iOS and Android.',
                'status' => 'active',
                'visibility' => 'private',
                'allow_join_requests' => false,
                'created_by' => $alaa->id,
            ],
            [
                'name' => 'Database Migration',
                'description' => 'Migrate legacy data to the new PostgreSQL database structure with zero downtime.',
                'status' => 'paused',
                'visibility' => 'private',
                'allow_join_requests' => false,
                'created_by' => $alaa->id,
            ],
            [
                'name' => 'API Integration',
                'description' => 'Integrate third-party APIs for payment processing, notifications, and analytics.',
                'status' => 'active',
                'visibility' => 'public',
                'allow_join_requests' => true,
                'created_by' => $alaa->id,
            ],
            [
                'name' => 'Internal Dashboard',
                'description' => 'Create an internal analytics dashboard for managers to track team productivity.',
                'status' => 'active',
                'visibility' => 'private',
                'allow_join_requests' => false,
                'created_by' => $alaa->id,
            ],
        ];

        foreach ($projects as $projectData) {
            Project::firstOrCreate(
                ['name' => $projectData['name']],
                $projectData
            );
        }
    }
}
