<?php

namespace App\Http\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class ProjectService
 *
 * Handles business logic for managing projects.
 */
class ProjectService
{
    /**
     * Retrieve a paginated list of projects with role-based filtering for developers.
     *
     * @param User $user The authenticated user model.
     * @return LengthAwarePaginator Paginated list of projects.
     */
    public function getAllProjects(User $user): LengthAwarePaginator
    {
        $query = Project::withCount('tasks')->with('creator:id,name,email');

        // If the user is a developer, filter projects assigned through tasks
        if ($user->role === 'developer') {
            $query->whereHas('tasks', function ($q) use ($user) {
                $q->whereHas('assignedUsers', function ($u) use ($user) {
                    $u->where('users.id', $user->id);
                });
            });
        }

        return $query->latest()->paginate(10);
    }

    /**
     * Create a new project instance in the database.
     *
     * @param array $data Validated input attributes for the new project.
     * @return Project The newly created project model instance.
     */
    public function createProject(array $data): Project
    {
        return Project::create($data);
    }

    /**
     * Fetch a single project by its primary key with related tasks and creator.
     *
     * @param int $projectId The unique ID of the project.
     * @return Project The project model instance with eager-loaded relations.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If project is not found.
     */
    public function getProjectById(int $projectId): Project
    {
        return Project::with(['tasks.assignedUsers', 'creator:id,name,email'])
            ->findOrFail($projectId);
    }

    /**
     * Update an existing project record with filtered non-null attributes.
     *
     * @param int $projectId The unique ID of the project to update.
     * @param array $data Array of updated values.
     * @return Project The updated project model instance.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If project is not found.
     */
    public function updateProject(int $projectId, array $data): Project
    {
        $project = Project::findOrFail($projectId);
        $project->update(array_filter($data));

        return $project;
    }

    /**
     * Delete a project record from the database by ID.
     *
     * @param int $projectId The unique ID of the project to delete.
     * @return bool True on successful deletion.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If project is not found.
     */
    public function deleteProject(int $projectId): bool
    {
        $project = Project::findOrFail($projectId);

        return $project->delete();
    }

    /**
     * Change the status attribute of a given project.
     *
     * @param int $projectId The unique ID of the target project.
     * @param string $status The new status string to apply.
     * @return Project The updated project model instance.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If project is not found.
     */
    public function changeProjectStatus(int $projectId, string $status): Project
    {
        $project = Project::findOrFail($projectId);
        $project->update(['status' => $status]);

        return $project;
    }
}
