<?php

namespace app\Http\Controllers\api;

use App\Events\TaskNotificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\ReorderTasksRequest;
use app\Http\Requests\Task\StoreGroupTaskRequest;
use App\Http\Requests\Task\StoreManagerTaskRequest;
use App\Http\Requests\Task\StoreSubTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Group;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskStatus;
use app\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    /**
     * Display a list of project tasks (only for project members).
     */
    public function index(Project $project, Request $request): JsonResponse
    {
        try {
            // Only project members/owners can view tasks
            $this->checkProjectAccess($project);

            $assigneeId = $request->input('assignee_id');
            $priority = $request->input('priority');
            $search = $request->input('search');
            $dueDateFrom = $request->input('due_date_from');
            $dueDateTo = $request->input('due_date_to');
            $dueDate = $request->input('due_date');

            $allowedSorts = ['id', 'title', 'priority', 'due_date', 'position', 'created_at', 'updated_at'];
            $sortBy = $request->input('sort_by', 'position');
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'position';
            }
            $sortDirection = $request->input('sort_direction', 'asc');
            if (!in_array($sortDirection, ['asc', 'desc'])) {
                $sortDirection = 'asc';
            }

            if ($assigneeId && !$project->hasUser($assigneeId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The specified assignee is not a member of this project'
                ], 422);
            }

            $tasksQuery = $project->tasks()
                ->with(['status', 'creator', 'assignee', 'taskAssignments.user', 'subTasks'])
                ->when($priority, fn($q) => $q->where('priority', $priority))
                ->when($assigneeId, fn($q) => $q->byAssignee($assigneeId))
                ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                }))
                ->when($dueDate, fn($q) => $q->whereDate('due_date', $dueDate))
                ->when($dueDateFrom, fn($q) => $q->whereDate('due_date', '>=', $dueDateFrom))
                ->when($dueDateTo, fn($q) => $q->whereDate('due_date', '<=', $dueDateTo))
                ->orderBy($sortBy, $sortDirection);

            $tasks = $tasksQuery->limit(100)->get();
            $tasks->loadMissing(['subTasks.status', 'subTasks.assignee', 'subTasks.taskAssignments']);

            return response()->json([
                'success' => true,
                'data' => TaskResource::collection($tasks),
                'total' => $tasks->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Fetching project tasks failed: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load tasks. Please try again later.'
            ], 500);
        }
    }

    /**
     * Create a new project task.
     * - If allow_subtasks = true → task becomes a parent task (cannot be assigned).
     * - Otherwise, normal assignable task.
     */
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->checkProjectManager($project);

        $statusId = $request->status_id ?? $project->taskStatuses()->first()?->id;
        if (!$statusId) {
            return response()->json([
                'success' => false,
                'message' => 'Please create task statuses first',
            ], 422);
        }

        $allowSubtasks = $request->input('allow_subtasks', false);
        $canBeAssigned = !$allowSubtasks; // parent tasks cannot be assigned

        // If it's a parent task, prevent assignment fields
        if ($allowSubtasks && ($request->has('assigned_to') || $request->has('assignees'))) {
            return response()->json([
                'success' => false,
                'message' => 'A parent task (with subtasks) cannot be assigned directly.',
            ], 422);
        }

        $maxPosition = Task::where('project_id', $project->id)
            ->where('status_id', $statusId)
            ->max('position') ?? -1;

        try {
            DB::beginTransaction();

            $task = Task::create([
                'project_id' => $project->id,
                'status_id' => $statusId,
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority ?? 'medium',
                'due_date' => $request->due_date,
                'created_by' => $request->user()->id,
                'assigned_to' => $canBeAssigned ? $request->assigned_to : null,
                'position' => $maxPosition + 1,
                'allow_subtasks' => $allowSubtasks,
                'can_be_assigned' => $canBeAssigned,
                'auto_status' => false, // project parent tasks do not auto-complete
            ]);

            if ($canBeAssigned && $request->has('assignees')) {
                $task->assignees()->sync($request->assignees);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Task creation failed: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task. Please try again later.',
            ], 500);
        }

        $task->load(['status', 'creator', 'assignee', 'assignees']);

        if ($task->assigned_to || $task->assignees->isNotEmpty()) {
            $userIds = [];
            if ($task->assigned_to) {
                $userIds[] = $task->assigned_to;
            }
            $userIds = array_merge($userIds, $task->assignees->pluck('id')->toArray());

            TaskNotificationEvent::dispatch(
                userIds: array_unique($userIds),
                scenario: 'assigned',
                task: $task,
                actor: $request->user(),
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => new TaskResource($task),
        ], 201);
    }

    /**
     * Create a task assigned to an entire group.
     * (type: groupTask)
     */
    public function storeGroupTask(StoreGroupTaskRequest $request, Project $project): JsonResponse
    {
        $this->checkProjectManager($project);

        $statusId = $request->status_id ?? $project->taskStatuses()->first()?->id;
        if (!$statusId) {
            return response()->json([
                'success' => false,
                'message' => 'Please create task statuses first',
            ], 422);
        }

        $group = Group::where('id', $request->group_id)
            ->where('project_id', $project->id)
            ->first();

        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => 'Group not found or does not belong to this project',
            ], 404);
        }

        $maxPosition = Task::where('project_id', $project->id)
            ->max('position') ?? -1;

        try {
            DB::beginTransaction();

            $task = Task::create([
                'project_id' => $project->id,
                'assigned_group_id' => $group->id,
                'status_id' => $statusId,
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority ?? 'medium',
                'due_date' => $request->due_date,
                'created_by' => $request->user()->id,
                'position' => $maxPosition + 1,
                'allow_subtasks' => false,
                'can_be_assigned' => true,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Group task creation failed: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'group_id' => $group->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create group task. Please try again later.',
            ], 500);
        }

        $task->load(['status', 'creator', 'assignedGroup']);

        if ($task->assignedGroup) {
            $groupMemberIds = $task->assignedGroup->members()->pluck('users.id')->toArray();

            if (!empty($groupMemberIds)) {
                TaskNotificationEvent::dispatch(
                    userIds: $groupMemberIds,
                    scenario: 'assigned',
                    task: $task,
                    actor: $request->user(),
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Group task created successfully',
            'data' => new TaskResource($task),
        ], 201);
    }

    /**
     * Create a manager task inside a group.
     * - If allow_subtasks = true → parent manager task (cannot be assigned, auto_status = true).
     * - Else → normal manager task (assignable).
     */
    public function storeManagerTask(StoreManagerTaskRequest $request, Project $project, Group $group): JsonResponse
    {
        $userId = $request->user()->id;
        $this->checkProjectManager($project);

        $statusId = $request->status_id ?? $project->taskStatuses()->first()?->id;
        if (!$statusId) {
            return response()->json([
                'success' => false,
                'message' => 'Please create task statuses first',
            ], 422);
        }

        $allowSubtasks = $request->input('allow_subtasks', false);
        $canBeAssigned = !$allowSubtasks;

        // Prevent assignment if parent task
        if ($allowSubtasks && ($request->has('assigned_to') || $request->has('assignees'))) {
            return response()->json([
                'success' => false,
                'message' => 'A parent manager task (with subtasks) cannot be assigned directly.',
            ], 422);
        }

        $maxPosition = Task::where('project_id', $project->id)
            ->where('group_id', $group->id)
            ->max('position') ?? -1;

        try {
            DB::beginTransaction();

            $task = Task::create([
                'project_id' => $project->id,
                'group_id' => $group->id,
                'status_id' => $statusId,
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority ?? 'medium',
                'due_date' => $request->due_date,
                'created_by' => $userId,
                'position' => $maxPosition + 1,
                'allow_subtasks' => $allowSubtasks,
                'auto_status' => $allowSubtasks,
                'can_be_assigned' => $canBeAssigned,
                'assigned_to' => ($canBeAssigned && $request->has('assigned_to')) ? $request->assigned_to : null,
            ]);

            if ($canBeAssigned && $request->has('assignees')) {
                $task->assignees()->sync($request->assignees);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Manager task creation failed: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'group_id' => $group->id,
                'user_id' => $userId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create manager task. Please try again later.',
            ], 500);
        }

        $task->load(['status', 'creator', 'group', 'assignee', 'assignees']);

        if (!$allowSubtasks && ($task->assigned_to || $task->assignees->isNotEmpty())) {
            $userIds = [];
            if ($task->assigned_to) {
                $userIds[] = $task->assigned_to;
            }
            $userIds = array_merge($userIds, $task->assignees->pluck('id')->toArray());

            TaskNotificationEvent::dispatch(
                userIds: array_unique($userIds),
                scenario: 'assigned',
                task: $task,
                actor: $request->user(),
            );
        }

        $message = $allowSubtasks
            ? 'Manager parent task created successfully (will auto-complete when all subtasks are done)'
            : 'Manager task created and assigned successfully';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new TaskResource($task),
        ], 201);
    }

    /**
     * Create a subtask under a parent task (either project parent or manager parent).
     * Subtasks are always assignable and require at least one assignee.
     */
    public function storeSubTask(StoreSubTaskRequest $request, Project $project, Group $group, Task $parentTask): JsonResponse
    {
        $userId = $request->user()->id;
        $this->checkProjectManager($project);

        // Validation: parent task must belong to the same project and group
        if ($parentTask->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Parent task does not belong to this project',
            ], 404);
        }

        if ($parentTask->group_id !== $group->id) {
            return response()->json([
                'success' => false,
                'message' => 'Parent task does not belong to this group',
            ], 404);
        }

        // Check that parent task allows subtasks
        if (!$parentTask->allow_subtasks) {
            return response()->json([
                'success' => false,
                'message' => 'The parent task does not allow subtasks.',
            ], 422);
        }

        $statusId = $request->status_id ?? $project->taskStatuses()->first()?->id;
        if (!$statusId) {
            return response()->json([
                'success' => false,
                'message' => 'Please create task statuses first',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $subTask = Task::create([
                'project_id' => $project->id,
                'group_id' => $group->id,
                'parent_task_id' => $parentTask->id,
                'status_id' => $statusId,
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority ?? 'medium',
                'due_date' => $request->due_date,
                'created_by' => $userId,
                'allow_subtasks' => false,
                'auto_status' => false,
                'can_be_assigned' => true,
            ]);

            foreach ($request->assigned_to as $assignedUserId) {
                TaskAssignment::create([
                    'task_id' => $subTask->id,
                    'user_id' => $assignedUserId,
                    'status_id' => $statusId,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Subtask creation failed: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'parent_task_id' => $parentTask->id,
                'user_id' => $userId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subtask. Please try again later.',
            ], 500);
        }

        $subTask->load(['status', 'creator', 'taskAssignments.user']);

        $subTaskAssigneeIds = $subTask->taskAssignments->pluck('user_id')->toArray();
        if (!empty($subTaskAssigneeIds)) {
            TaskNotificationEvent::dispatch(
                userIds: $subTaskAssigneeIds,
                scenario: 'assigned',
                task: $subTask,
                actor: $request->user(),
            );
        }

        if ($parentTask->auto_status) {
            $parentTask->syncStatusFromSubtasks();
        }

        return response()->json([
            'success' => true,
            'message' => 'Subtask created and assigned successfully',
            'data' => new TaskResource($subTask),
        ], 201);
    }
    public function show(Request $request, Project $project, Task $task): JsonResponse
    {
        try {
            // Allow access only to project members/owners
            if (!$project->hasUser($request->user()->id) && !$project->isOwner($request->user()->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this task'
                ], 403);
            }


            if ($task->project_id !== $project->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task does not belong to this project',
                ], 404);
            }

            // Load all necessary relations for TaskResource
            $task->load([
                'status',
                'creator',
                'assignee',
                'taskAssignments.user',
                'taskAssignments.status',
                'dependencies',
                'comments.user',
                'subTasks.status',
                'subTasks.assignee',
                'subTasks.taskAssignments',
                'group',
                'assignedGroup',
                'parentTask',
            ]);

            return response()->json([
                'success' => true,
                'data' => new TaskResource($task),
            ]);
        } catch (\Exception $e) {
            Log::error('Fetching task details failed: ' . $e->getMessage(), [
                'task_id' => $task->id,
                'project_id' => $project->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load task details. Please try again later.',
            ], 500);
        }
    }


    /**
     * Update a task.
     * Only the project owner or task creator can update basic fields.
     * Assignment updates are allowed only for assignable tasks and by the project owner.
     */
    public function update(UpdateTaskRequest $request, Project $project, Task $task): JsonResponse
    {
        // 1. Verify the task belongs to the project
        if ($task->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Task does not belong to this project',
            ], 404);
        }

        $userId = $request->user()->id;
        $isOwner = $project->isOwner($userId);
        $isTaskCreator = $task->created_by === $userId;

        if (!$isOwner && !$isTaskCreator) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this task.',
            ], 403);
        }

        // 2. Check if the task allows assignment (parent tasks cannot be assigned)
        if (!$task->canBeAssigned() && ($request->has('assigned_to') || $request->has('assignees'))) {
            return response()->json([
                'success' => false,
                'message' => 'This task cannot be assigned because it is a parent task (has subtasks).',
            ], 422);
        }

        // 3. Ensure all assignees are members of the project
        if ($request->has('assignees')) {
            $assignees = $request->assignees;
            $validMembers = $project->users()->whereIn('user_id', $assignees)->pluck('user_id')->toArray();
            $invalid = array_diff($assignees, $validMembers);
            if (!empty($invalid)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some users are not members of this project: ' . implode(', ', $invalid),
                ], 422);
            }
        }

        // 4. Allowed fields for update (exclude group_id, allow_subtasks, auto_status, can_be_assigned, assigned_group_id)
        $allowedFields = ['title', 'description', 'priority', 'due_date', 'assigned_to', 'position'];
        if ($isOwner) {
            $updateData = $request->only($allowedFields);
        } else {
            // Task creator cannot update assignment or position
            $updateData = $request->only(['title', 'description', 'priority', 'due_date']);
        }

        // 5. Prevent direct position update (use reorder / updateStatus endpoints instead)
        if (isset($updateData['position'])) {
            return response()->json([
                'success' => false,
                'message' => 'Position cannot be updated directly. Use reorder endpoint.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $task->update($updateData);

            if ($isOwner && $request->has('assignees') && $task->canBeAssigned()) {
                $task->assignees()->sync($request->assignees);
            }

            DB::commit();

            $task->load(['status', 'creator', 'assignee', 'assignees']);

            return response()->json([
                'success' => true,
                'message' => 'Task updated successfully',
                'data' => new TaskResource($task),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Task update failed: ' . $e->getMessage(), [
                'task_id' => $task->id,
                'user_id' => $userId,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task. Please try again later.',
            ], 500);
        }
    }


    /**
     * Change task status (move to another column) and reorder automatically.
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Project $project, Task $task): JsonResponse
    {
        // 1. Verify task belongs to the project
        if ($task->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Task does not belong to this project',
            ], 404);
        }

        $userId = $request->user()->id;
        $userRole = $project->getUserRole($userId);
        $isOwner = $project->isOwner($userId);
        $isManager = $userRole === 'manager';
        $isUser = $userRole === 'user';
        $isTaskAssignee = ($task->assigned_to === $userId) || $task->taskAssignments()->where('user_id', $userId)->exists();

        if (!($isOwner || $isManager || ($isUser && $isTaskAssignee))) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to change task status',
            ], 403);
        }

        $oldStatusId = $task->status_id;
        $newStatusId = $request->status_id;

        // 2. Ensure the new status belongs to the same project
        if (!$project->taskStatuses()->where('id', $newStatusId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'The selected status does not belong to this project',
            ], 422);
        }

        // 3. Prevent changing status of a parent task that has subtasks (optional, but recommended)
        if ($task->allow_subtasks && $task->subTasks()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot manually change status of a parent task that has subtasks. Its status is automatically managed.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Shift positions in old status (remove gap)
            Task::where('project_id', $project->id)
                ->where('status_id', $oldStatusId)
                ->where('position', '>', $task->position)
                ->decrement('position');

            // Calculate new position (end of new status by default)
            $newPosition = $request->position ??
                Task::where('project_id', $project->id)
                    ->where('status_id', $newStatusId)
                    ->max('position') + 1;

            // Shift positions in new status (make room)
            Task::where('project_id', $project->id)
                ->where('status_id', $newStatusId)
                ->where('position', '>=', $newPosition)
                ->increment('position');

            // Update task
            $task->update([
                'status_id' => $newStatusId,
                'position' => $newPosition,
            ]);

            // Auto-complete if the new status is "Done" (case-insensitive)
            $status = TaskStatus::find($newStatusId);
            if ($status && strtolower($status->name) === 'done') {
                $task->complete();
            }

            DB::commit();

            // If this task is a subtask, sync parent task status after status change
            if ($task->parent_task_id) {
                $parent = $task->parentTask;
                if ($parent && $parent->auto_status) {
                    $parent->syncStatusFromSubtasks();
                }
            }

            $task->load(['status', 'creator', 'assignee', 'assignees']);

            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully',
                'data' => new TaskResource($task),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Task status update failed: ' . $e->getMessage(), [
                'task_id' => $task->id,
                'project_id' => $project->id,
                'user_id' => $userId,
                'old_status_id' => $oldStatusId,
                'new_status_id' => $newStatusId,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status. Please try again later.',
            ], 500);
        }
    }
    // public function updateTaskAssignmentStatus(Request $request, Task $task, int $assignmentId): JsonResponse
    // {
    //     $userId = $request->user()->id;

    //     $assignment = TaskAssignment::where('id', $assignmentId)
    //         ->where('task_id', $task->id)
    //         ->first();

    //     if (!$assignment) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Assignment not found'
    //         ], 404);
    //     }

    //     if ($assignment->user_id !== $userId) {
    //         $project = $task->project;
    //         if (!$project->isOwner($userId) && !$task->group?->isManager($userId)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'You do not have permission to update this assignment'
    //             ], 403);
    //         }
    //     }

    //     $request->validate([
    //         'status_id' => 'required|exists:task_statuses,id',
    //     ]);

    //     $doneStatus = $task->project->taskStatuses()->where('name', 'Done')->first();

    //     $assignment->update([
    //         'status_id' => $request->status_id,
    //         'completed_at' => ($doneStatus && $request->status_id === $doneStatus->id) ? now() : null
    //     ]);

    //     if ($task->auto_status && $assignment->completed_at) {
    //         $task->updateAutoStatus();
    //     }

    //     // Sync parent task status if this task is a subtask
    //     if ($task->parent_task_id) {
    //         $parentTask = $task->parentTask;
    //         if ($parentTask && $parentTask->auto_status) {
    //             $parentTask->syncStatusFromSubtasks();
    //         }
    //     } elseif ($task->allow_subtasks && $task->auto_status) {
    //         // If this task itself is a parent, sync its status based on its own subtasks
    //         $task->syncStatusFromSubtasks();
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Assignment status updated successfully',
    //         'data' => [
    //             'assignment_id' => $assignment->id,
    //             'status_id' => $assignment->status_id,
    //             'completed_at' => $assignment->completed_at
    //         ]
    //     ]);
    // }

    /**
     * Reorder multiple tasks (change position and/or status).
     * Only project owner or manager can perform this action.
     */
    public function reorder(ReorderTasksRequest $request, Project $project): JsonResponse
    {
        $this->checkProjectManager($project);

        $tasksData = $request->input('tasks');

        // 1. Verify all tasks belong to the project
        $taskIds = array_column($tasksData, 'id');
        $validTaskIds = Task::where('project_id', $project->id)->whereIn('id', $taskIds)->pluck('id')->toArray();
        $invalidIds = array_diff($taskIds, $validTaskIds);
        if (!empty($invalidIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some tasks do not belong to this project: ' . implode(', ', $invalidIds),
            ], 422);
        }

        // 2. Verify all status_ids belong to the project
        $statusIds = array_unique(array_column($tasksData, 'status_id'));
        $validStatusIds = $project->taskStatuses()->whereIn('id', $statusIds)->pluck('id')->toArray();
        $invalidStatusIds = array_diff($statusIds, $validStatusIds);
        if (!empty($invalidStatusIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some statuses do not belong to this project: ' . implode(', ', $invalidStatusIds),
            ], 422);
        }

        // 3. Optional: Prevent reordering parent tasks that have subtasks (if desired)
        // This depends on your business logic. If you want to allow manual override, skip this.
        $parentTasks = Task::whereIn('id', $taskIds)
            ->where('allow_subtasks', true)
            ->whereHas('subTasks')
            ->get();
        if ($parentTasks->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reorder parent tasks that have subtasks. Update their status via subtask completion instead.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            foreach ($tasksData as $taskData) {
                Task::where('id', $taskData['id'])
                    ->where('project_id', $project->id)
                    ->update([
                        'position' => $taskData['position'],
                        'status_id' => $taskData['status_id'],
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tasks reordered successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Task reorder failed: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => request()->user()->id,
                'tasks_data' => $tasksData,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder tasks. Please try again later.',
            ], 500);
        }
    }













    /**
     * Get all completed tasks of the project.
     */
    public function getCompletedTasks(Project $project, Request $request): JsonResponse
    {
        try {
            $this->checkProjectAccess($project);

            $tasks = $project->tasks()
                ->whereNotNull('completed_at')
                ->with(['status', 'creator', 'assignee', 'taskAssignments.user'])
                ->orderBy('completed_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => TaskResource::collection($tasks),
                'total' => $tasks->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Fetching completed tasks failed: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load completed tasks. Please try again later.'
            ], 500);
        }
    }
    /**
     * Get all assigned tasks (tasks that have at least one assignee or assigned group).
     */
    public function getAssignedTasks(Project $project, Request $request): JsonResponse
    {
        try {
            $this->checkProjectAccess($project);

            $tasks = $project->tasks()
                ->where(function ($q) {
                    $q->whereNotNull('assigned_to')
                        ->orWhereNotNull('assigned_group_id')
                        ->orWhereHas('assignees');
                })
                ->with(['status', 'creator', 'assignee', 'taskAssignments.user', 'assignedGroup'])
                ->orderBy('due_date')
                ->get();

            return response()->json([
                'success' => true,
                'data' => TaskResource::collection($tasks),
                'total' => $tasks->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Fetching assigned tasks failed: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load assigned tasks. Please try again later.'
            ], 500);
        }
    }
    /**
     * Get all unassigned tasks (no assignee, no assignments, no assigned group).
     */
    public function getUnassignedTasks(Project $project, Request $request): JsonResponse
    {
        try {
            $this->checkProjectAccess($project);

            $tasks = $project->tasks()
                ->whereNull('assigned_to')
                ->whereNull('assigned_group_id')
                ->whereDoesntHave('assignees')
                ->with(['status', 'creator'])
                ->orderBy('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'data' => TaskResource::collection($tasks),
                'total' => $tasks->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Fetching unassigned tasks failed: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load unassigned tasks. Please try again later.'
            ], 500);
        }
    }









    /**
     * Ensure the authenticated user is a member or owner of the project.
     */
    private function checkProjectAccess(Project $project): void
    {
        $userId = request()->user()->id;
        if (!$project->isOwner($userId) && !$project->hasUser($userId)) {
            abort(403, 'You must be a member of this project to access its tasks.');
        }
    }
    private function checkProjectManager(Project $project): void
    {
        $userId = request()->user()->id;
        if (!$project->isOwner($userId) && !$project->isManager($userId)) {
            abort(403, 'You do not have permission to manage tasks');
        }
    }

    private function checkTaskManagePermission(Project $project, User $user): void
    {
        $isOwner = $project->isOwner($user->id);
        if (!$isOwner) {
            abort(403, 'You do not have permission to manage tasks.');
        }
    }
















    /**
     * Soft delete a task.
     * Adjusts positions of remaining tasks in the same status.
     * Prevents deletion if the task has subtasks (parent task).
     */
    public function destroy(Project $project, Task $task): JsonResponse
    {
        // Verify task belongs to the project
        if ($task->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Task does not belong to this project',
            ], 404);
        }

        // Only project owner or manager can delete tasks
        $this->checkProjectManager($project);

        // Prevent deletion if task has subtasks
        if ($task->subTasks()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a parent task that has subtasks. Delete or reassign subtasks first.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Reorder: shift up tasks that were after this one in the same status
            Task::where('project_id', $project->id)
                ->where('status_id', $task->status_id)
                ->where('position', '>', $task->position)
                ->decrement('position');

            // Soft delete the task
            $task->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Task deletion failed: ' . $e->getMessage(), [
                'task_id' => $task->id,
                'project_id' => $project->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task. Please try again later.',
            ], 500);
        }
    }

    /**
     * Display soft-deleted tasks (trash) of the project.
     * Only project owner or manager can view trashed tasks.
     */
    public function trashed(Project $project, Request $request): JsonResponse
    {
        try {
            // Only owner or manager can view trash (not regular members)
            $this->checkProjectManager($project);

            $tasks = Task::onlyTrashed()
                ->where('project_id', $project->id)
                ->with(['status', 'creator', 'assignee'])
                ->orderBy('deleted_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => TaskResource::collection($tasks),
                'total' => $tasks->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Fetching trashed tasks failed: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load trashed tasks. Please try again later.'
            ], 500);
        }
    }


    /**
     * Restore a soft-deleted task.
     * Only the project owner can perform this action.
     */
    public function restoreTask(Project $project, Task $task, Request $request): JsonResponse
    {
        // 1. Verify task belongs to the project
        if ($task->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Task does not belong to this project',
            ], 404);
        }

        // 2. Ensure the task is actually soft-deleted
        if (!$task->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Task is not deleted. Nothing to restore.',
            ], 422);
        }

        // 3. Authorization: only project owner
        $this->checkTaskManagePermission($project, $request->user());

        try {
            DB::beginTransaction();

            // Restore the task (also restores assignments, comments, subtasks via model event)
            $task->restore();

            DB::commit();

            // Load necessary relations for response
            $task->load(['status', 'creator', 'assignee']);

            return response()->json([
                'success' => true,
                'message' => 'Task restored successfully.',
                'data' => new TaskResource($task),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Task restore failed: ' . $e->getMessage(), [
                'task_id' => $task->id,
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore task. Please try again later.',
            ], 500);
        }
    }


    /**
     * Permanently delete a soft-deleted task (including all relations).
     * Only the project owner can perform this action.
     */
    public function forceDeleteTask(Project $project, Task $task, Request $request): JsonResponse
    {
        // 1. Verify task belongs to the project
        if ($task->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Task does not belong to this project',
            ], 404);
        }

        // 2. Ensure the task is soft-deleted
        if (!$task->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'Task is not deleted. Use delete endpoint first.',
            ], 422);
        }

        // 3. Authorization: only project owner
        $this->checkTaskManagePermission($project, $request->user());

        try {
            DB::beginTransaction();

            // Force delete the task (model booted will handle assignments, comments, subtasks, dependencies)
            $task->forceDelete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task permanently deleted.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Force delete task failed: ' . $e->getMessage(), [
                'task_id' => $task->id,
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to permanently delete task. Please try again later.',
            ], 500);
        }
    }

    /**
     * Permanently delete all soft-deleted tasks in the project.
     * Only project owner or manager can perform this action
     */
    public function emptyTrash(Project $project, Request $request): JsonResponse
    {
        // 1. Authorization: owner or manager
        $this->checkProjectManager($project);

        // 2. Get all trashed tasks of this project (ensure they are Task models)
        $trashedTasks = Task::onlyTrashed()
            ->where('project_id', $project->id)
            ->get();

        if ($trashedTasks->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Trash is already empty.',
                'deleted_count' => 0,
            ]);
        }

        $deletedCount = 0;
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($trashedTasks as $task) {
                // Safety check: ensure it's a Task model
                if (!$task instanceof Task) {
                    $errors[] = [
                        'task_id' => $task->id ?? 'unknown',
                        'message' => 'Invalid task object, skipping.',
                    ];
                    continue;
                }

                try {
                    $task->forceDelete(); // triggers model events (cleans up assignments, comments, subtasks)
                    $deletedCount++;
                } catch (\Exception $e) {
                    // If any deletion fails, we roll back everything (atomicity)
                    throw new \Exception("Failed to delete task ID {$task->id}: " . $e->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $deletedCount === 1
                    ? "{$deletedCount} task permanently deleted from trash."
                    : "{$deletedCount} tasks permanently deleted from trash.",
                'deleted_count' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Empty trash failed: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to empty trash. Please try again later.',
                'errors' => $errors,
            ], 500);
        }
    }



    public function myPendingTasks(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;

        $sortBy = $request->input('sort_by', 'due_date');
        $sortDir = $request->input('sort_direction', 'asc');

        $allowedSorts = ['due_date', 'created_at', 'priority', 'title'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'due_date';
        }

        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }

        $tasks = Task::with(['project', 'status', 'creator', 'assignee'])
            ->whereNull('completed_at')
            ->where(function ($query) use ($userId) {
                $query->where('assigned_to', $userId)
                    ->orWhereHas('assignees', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
            })
            ->orderBy($sortBy, $sortDir)
            ->get();

        return response()->json([
            'success' => true,
            'data' => TaskResource::collection($tasks),
            'total' => $tasks->count(),
        ]);
    }
}
