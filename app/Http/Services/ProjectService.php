<?php

namespace App\Http\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectService
{
    /**
     * جلب قائمة المشاريع مع فلترة للمطورين وترقيم التنائج
     */
    public function getAllProjects(User $user): LengthAwarePaginator
    {
        $query = Project::withCount('tasks')->with('creator:id,name,email');

        // إذا كان الدور Developer تظهر المشاريع الموكلة إليه فقط
        if ($user->role === 'developer') {
            $query->whereHas('tasks', function ($q) use ($user) {
                $q->whereHas('assignedUsers', function ($u) use ($user) {
                    $u->where('users.id', $user->id);
                });
            });
        }

        return $query->latest()->paginate(10);
    }

    public function createProject(array $data): Project
    {
        return Project::create($data);
    }

    public function getProjectById(int $projectId): Project
    {
        return Project::with(['tasks.assignedUsers', 'creator:id,name,email'])
            ->findOrFail($projectId);
    }

    public function updateProject(int $projectId, array $data): Project
    {
        $project = Project::findOrFail($projectId);
        $project->update(array_filter($data));

        return $project;
    }

    public function deleteProject(int $projectId): bool
    {
        $project = Project::findOrFail($projectId);

        return $project->delete();
    }

    public function changeProjectStatus(int $projectId, string $status): Project
    {
        $project = Project::findOrFail($projectId);
        $project->update(['status' => $status]);

        return $project;
    }
}
