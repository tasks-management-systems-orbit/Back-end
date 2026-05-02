<?php

namespace app\Http\Requests\JoinRequest;

use Illuminate\Foundation\Http\FormRequest;

class ProcessJoinRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        return $project && $project->isOwner($this->user()->id);
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:approved,rejected',
            'role' => 'required_if:status,approved|in:user,manager,observer',
        ];
    }
}
