<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Project;
use App\Models\TaskTransfer;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class TaskTransferService
{
    protected ChainService $chainService;

    public function __construct(ChainService $chainService)
    {
        $this->chainService = $chainService;
    }

    public function transfer(int $taskId, int $targetProjectId, int $userId, ?string $note = null): Task
    {
        $task = Task::with(['project', 'subTasks'])->findOrFail($taskId);
        $targetProject = Project::findOrFail($targetProjectId);

        // Validate transfer conditions
        $this->validateTransfer($task, $targetProject);

        $sourceProject = $task->project;
        $defaultStatus = $targetProject->taskStatuses()->first();
        $newStatusId = $defaultStatus ? $defaultStatus->id : null;

        DB::beginTransaction();

        try {
            // Clone the task AND its subtasks
            $newTask = $task->cloneForTransfer($targetProjectId, $newStatusId, $userId);

            // Archive the original task
            $task->archive();

            // 1. Create transfer record for the parent task
            TaskTransfer::create([
                'task_id' => $newTask->id,
                'from_project_id' => $sourceProject->id,
                'to_project_id' => $targetProject->id,
                'from_task_id' => $task->id,
                'to_task_id' => $newTask->id,
                'transferred_by' => $userId,
                'note' => $note . ' (Parent task)',
                'transferred_at' => now(),
            ]);

            // 2. Create transfer records for all subtasks
            $originalSubtasks = $task->subTasks;
            $newSubtasks = $newTask->subTasks;

            foreach ($originalSubtasks as $index => $originalSubtask) {
                if (isset($newSubtasks[$index])) {
                    $newSubtask = $newSubtasks[$index];
                    TaskTransfer::create([
                        'task_id' => $newSubtask->id,
                        'from_project_id' => $sourceProject->id,
                        'to_project_id' => $targetProject->id,
                        'from_task_id' => $originalSubtask->id,
                        'to_task_id' => $newSubtask->id,
                        'transferred_by' => $userId,
                        'note' => $note . ' (Subtask)',
                        'transferred_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return $newTask->load(['project', 'status', 'creator', 'subTasks']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function validateTransfer(Task $task, Project $targetProject): void
    {
        // 1. Check if task is already archived
        if ($task->is_archived) {
            throw ValidationException::withMessages([
                'task' => 'Archived tasks cannot be transferred.'
            ]);
        }

        // 2. Check if subtasks are completed
        if (!$task->canBeTransferred()) {
            throw ValidationException::withMessages([
                'task' => 'Cannot transfer task because it has incomplete subtasks.'
            ]);
        }

        // 3. Check if both projects are in the same chain
        $sourceChain = $this->chainService->getProjectChain($task->project_id);
        $targetChain = $this->chainService->getProjectChain($targetProject->id);

        if (!$sourceChain || !$targetChain || $sourceChain->id !== $targetChain->id) {
            throw ValidationException::withMessages([
                'target_project' => 'Target project must be in the same chain.'
            ]);
        }

        // 4. Check if target project is different from source
        if ($task->project_id === $targetProject->id) {
            throw ValidationException::withMessages([
                'target_project' => 'Target project must be different from source project.'
            ]);
        }

        // 5. Check if user is allowed to transfer (owner of source project)
        // This can be extended for project managers as needed
        // We'll handle authorization in the controller
    }

    public function getTransferHistory(int $taskId)
    {
        return TaskTransfer::where('task_id', $taskId)
            ->orWhere('from_task_id', $taskId)
            ->orWhere('to_task_id', $taskId)
            ->with(['fromProject', 'toProject', 'transferredBy'])
            ->orderBy('transferred_at', 'asc')
            ->get();
    }
}