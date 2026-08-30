<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Task::with(['project', 'creator', 'assignedUsers']);

        if ($user->role === 'developer') {
            $query->whereHas('assignedUsers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $tasks = $query->latest()->get();

        return response()->json(['data' => $tasks]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'project_id'     => ['required', 'exists:projects,id'],
            'status'         => ['nullable', 'in:todo,in_progress,review,done'],
            'priority'       => ['nullable', 'in:low,medium,high'],
            'due_date'       => ['nullable', 'date'],
            'assigned_users' => ['nullable', 'array'],
            'assigned_users.*' => ['exists:users,id'],
        ]);

        $task = Task::create([
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'project_id'  => $validated['project_id'],
            'created_by'  => $request->user()->id,
            'status'      => $validated['status'] ?? 'todo',
            'priority'    => $validated['priority'] ?? 'medium',
            'due_date'    => $validated['due_date'] ?? null,
        ]);

        if (!empty($validated['assigned_users'])) {
            $task->assignedUsers()->sync($validated['assigned_users']);
        }

        return response()->json([
            'message' => 'Task created successfully',
            'data' => $task->load(['project', 'creator', 'assignedUsers']),
        ], 201);
    }

    public function show(Request $request, int $taskId): JsonResponse
    {
        $task = Task::with(['project', 'creator', 'assignedUsers', 'comments.user'])->findOrFail($taskId);

        $user = $request->user();

        // Developer can only view tasks they're assigned to.
        if ($user->role === 'developer' && !$task->assignedUsers->contains($user->id)) {
            return response()->json(['message' => 'Access Denied!'], 403);
        }

        return response()->json(['data' => $task]);
    }

   
    public function update(Request $request, int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);

        $validated = $request->validate([
            'title'          => ['nullable', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'project_id'     => ['nullable', 'exists:projects,id'],
            'priority'       => ['nullable', 'in:low,medium,high'],
            'due_date'       => ['nullable', 'date'],
            'assigned_users' => ['nullable', 'array'],
            'assigned_users.*' => ['exists:users,id'],
        ]);

        $task->update($validated);

        if (array_key_exists('assigned_users', $validated)) {
            $task->assignedUsers()->sync($validated['assigned_users'] ?? []);
        }

        return response()->json([
            'message' => 'Task updated successfully',
            'data' => $task->load(['project', 'creator', 'assignedUsers']),
        ]);
    }

    public function destroy(int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);
        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }

 
    public function changeStatus(Request $request, int $taskId): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:todo,in_progress,review,done'],
        ]);

        $task = Task::findOrFail($taskId);
        $user = $request->user();

        if ($user->role === 'developer' && !$task->assignedUsers->contains($user->id)) {
            return response()->json(['message' => 'Access Denied!'], 403);
        }

        $task->update(['status' => $request->input('status')]);

        return response()->json([
            'message' => 'Task status updated successfully',
            'data' => $task,
        ]);
    }

   
    public function addComment(Request $request, int $taskId): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $task = Task::findOrFail($taskId);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        return response()->json([
            'message' => 'Comment added successfully',
            'data' => $comment->load('user'),
        ], 201);
    }

  
    public function comments(int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);

        return response()->json(['data' => $task->comments()->with('user')->latest()->get()]);
    }
}
