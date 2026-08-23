<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\CreateProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private ProjectService $projectService)
    {
    }

    // public function index(Request $request): JsonResponse
    // {
    //     $projects = $this->projectService->getAllProjects($request->user());

    //     return response()->json($projects);
    // }

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

    public function show(int $projectId): JsonResponse
    {
        $project = $this->projectService->getProjectById($projectId);

        return response()->json($project);
    }

    public function update(UpdateProjectRequest $request, int $projectId): JsonResponse
    {
        $project = $this->projectService->updateProject($projectId, $request->validated());

        return response()->json([
            'message' => 'Project updated successfully',
            'data' => $project,
        ]);
    }

    public function destroy(int $projectId): JsonResponse
    {
        $this->projectService->deleteProject($projectId);

        return response()->json([
            'message' => 'Project deleted successfully',
        ]);
    }

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
