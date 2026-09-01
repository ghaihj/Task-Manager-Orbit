<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Dashboard')]
class DashboardController extends Controller
{
    #[Endpoint(title: 'Get dashboard statistics', description: 'Return dashboard statistics for the current user or the full dataset based on role.')]
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'developer') {
            $tasksQuery = Task::whereHas('assignedUsers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });

            $stats = [
                'scope' => 'own_tasks',
                'total_tasks' => (clone $tasksQuery)->count(),
                'todo' => (clone $tasksQuery)->where('status', 'todo')->count(),
                'in_progress' => (clone $tasksQuery)->where('status', 'in_progress')->count(),
                'review' => (clone $tasksQuery)->where('status', 'review')->count(),
                'done' => (clone $tasksQuery)->where('status', 'done')->count(),
                'high_priority' => (clone $tasksQuery)->where('priority', 'high')->count(),
                'overdue' => (clone $tasksQuery)->whereNotNull('due_date')
                    ->where('due_date', '<', now())
                    ->where('status', '!=', 'done')
                    ->count(),
            ];

            return response()->json(['data' => $stats]);
        }

        // Admin / Editor — global stats
        $stats = [
            'scope' => 'all_data',
            'total_projects' => Project::count(),
            'active_projects' => Project::where('status', 'active')->count(),
            'total_tasks' => Task::count(),
            'todo' => Task::where('status', 'todo')->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'review' => Task::where('status', 'review')->count(),
            'done' => Task::where('status', 'done')->count(),
            'high_priority' => Task::where('priority', 'high')->count(),
            'overdue' => Task::whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->where('status', '!=', 'done')
                ->count(),
        ];

        return response()->json(['data' => $stats]);
    }

    #[Endpoint(title: 'List users', description: 'Return the public user list for dashboard use.')]
    public function users(): JsonResponse
    {
        return response()->json(['data' => \App\Models\User::select('id', 'name', 'email', 'role')->get()]);
    }
}
