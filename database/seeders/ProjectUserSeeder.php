<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectUserSeeder extends Seeder
{
    public function run(): void
    {
        $alaa = User::where('email', 'alaa.gbh0@gmail.com')->first();
        $allUsers = User::where('id', '!=', $alaa->id)->pluck('id')->toArray();
        $roles = ['manager', 'user', 'observer'];

        $projects = Project::all();

        foreach ($projects as $project) {
            // Ensure alaa is owner
            if (! $project->users()->where('user_id', $alaa->id)->exists()) {
                $project->users()->attach($alaa->id, ['role' => 'owner']);
            }

            // Attach 2 to 4 random other users
            shuffle($allUsers);
            $memberCount = rand(2, 4);
            $selectedUsers = array_slice($allUsers, 0, $memberCount);

            foreach ($selectedUsers as $userId) {
                $role = $roles[array_rand($roles)];
                if (! $project->users()->where('user_id', $userId)->exists()) {
                    $project->users()->attach($userId, ['role' => $role]);
                }
            }
        }
    }
}
