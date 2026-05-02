<?php

namespace app\Http\Requests\JoinRequest;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Project;

class SendJoinRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = Project::find($this->route('project'));
        if (!$project) return false;

        if (in_array($project->status, ['completed', 'paused'])) {
            return false;
        }


        $user = $this->user();

        if ($project->isOwner($user->id) || $project->hasUser($user->id)) {
            return false;
        }

        return $project->allow_join_requests;
    }

    public function rules(): array
    {
        return [
            'message' => 'nullable|string|max:500',
        ];
    }
}
