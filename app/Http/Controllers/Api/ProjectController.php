<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\CreateProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\Project\ProjectCollection;
use App\Http\Resources\Project\ProjectResource;
use App\Models\Project;
use App\Services\Project\CalculateProjectService;
use App\Services\Project\CreateProjectService;
use App\Services\Project\UpdateProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    private CreateProjectService $createService;
    private CalculateProjectService $calculateService;
    private UpdateProjectService $updateService;

    public function __construct(
        CreateProjectService $createService,
        UpdateProjectService $updateService,
        CalculateProjectService $calculateService
    )
    {
        $this->createService = $createService;
        $this->updateService = $updateService;
        $this->calculateService = $calculateService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(
            new ProjectCollection(Project::getAll())
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateProjectRequest $request
     * @return JsonResponse
     */
    public function store(CreateProjectRequest $request): JsonResponse
    {
        $project = $this->createService->create($request);

        return response()->json(new ProjectResource($project), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param string $projectId
     * @param string $revisionId
     * @return JsonResponse
     */
    public function show(string $projectId, string $revisionId): JsonResponse
    {
        $project = Project::getByCondition($projectId, $revisionId);

        if (! $project) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(
            new ProjectResource($this->calculateService->get($project))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProjectRequest $request
     * @param string $projectId
     * @return JsonResponse
     */
    public function update(UpdateProjectRequest $request, string $projectId): JsonResponse
    {
        $updatedProject = Project::getByCondition($projectId, $request->project['revision_id']);

        $newProject = $this->updateService->update($updatedProject, $request->project);

        return response()->json(
            new ProjectResource($this->calculateService->get($newProject))
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Project $project
     * @return JsonResponse
     */
    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json(null, 204);
    }

    /**
     * Calculate project
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculate(Request $request): JsonResponse
    {
        return response()->json(
            new ProjectResource($this->calculateService->get($request->project))
        );
    }
}
