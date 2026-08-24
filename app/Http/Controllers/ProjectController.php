<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\CreateProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class ProjectController
 *
 * Handles incoming API requests for project management endpoints.
 */
class ProjectController extends Controller
{
    /**
     * ProjectController constructor.
     *
     * @param ProjectService $projectService Service layer handling project operations.
     */
    public function __construct(private ProjectService $projectService)
    {
    }

    // public function index(Request $request): JsonResponse
    // {
    //     $projects = $this->projectService->getAllProjects($request->user());

    //     return response()->json($projects);
    // }

    /**
     * Store a newly created project in storage.
     *
     * @param CreateProjectRequest $request Validated request containing project attributes.
     * @return JsonResponse JSON payload containing the created project data and a 201 status code.
     */
    public function store(CreateProjectRequest $request): JsonResponse
    {
        $data = $request->validated();
        // $data['created_by'] = $request->user()->id;

        $project = $this->projectService->createProject($data);

        return response()->json([
            'message' => 'Project created successfully',
            'data' => $project,
        ], 201);
    }

    /**
     * Display the specified project details.
     *
     * @param int $projectId Unique identifier of the project.
     * @return JsonResponse JSON payload containing project details and relations.
     */
    public function show(int $projectId): JsonResponse
    {
        $project = $this->projectService->getProjectById($projectId);

        return response()->json($project);
    }

    /**
     * Update the specified project in storage.
     *
     * @param UpdateProjectRequest $request Validated request containing updatable project fields.
     * @param int $projectId Unique identifier of the project to update.
     * @return JsonResponse JSON payload containing success message and updated project model.
     */
    public function update(UpdateProjectRequest $request, int $projectId): JsonResponse
    {
        $project = $this->projectService->updateProject($projectId, $request->validated());

        return response()->json([
            'message' => 'Project updated successfully',
            'data' => $project,
        ]);
    }

    /**
     * Remove the specified project from storage.
     *
     * @param int $projectId Unique identifier of the project to delete.
     * @return JsonResponse JSON payload with a success message.
     */
    public function destroy(int $projectId): JsonResponse
    {
        $this->projectService->deleteProject($projectId);

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }

    /**
     * Change the current status of a specific project.
     *
     * @param Request $request Request containing the new status value.
     * @param int $projectId Unique identifier of the project.
     * @return JsonResponse JSON payload containing status update confirmation and project data.
     */
    public function changeStatus(Request $request, int $projectId): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:active,on_hold,completed'],
        ]);

        $project = $this->projectService->changeProjectStatus($projectId, $request->input('status'));

        return response()->json([
            'message' => 'Project status updated successfully',
            'data' => $project,
        ]);
    }
}
