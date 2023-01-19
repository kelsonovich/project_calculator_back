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
    public function index()
    {
        return response()->json(
            new ProjectCollection(Project::all())
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateProjectRequest $request
     * @return JsonResponse
     */
    public function store(CreateProjectRequest $request)
    {
        $project = $this->createService->create($request);

        return response()->json(new ProjectResource($project), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $projectId
     * @return JsonResponse
     */
    public function show(int $projectId, $revisionId = null)
    {
        $project = Project::getByCondition($projectId, $revisionId);

        return response()->json(
            new ProjectResource($this->calculateService->get($project))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProjectRequest $request
     * @param \App\Models\Project $project
     * @return JsonResponse
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        dd();

        dd(1, 2, 3, 4, 5);

        return response()->json(
            $this->updateService->update(
                new ProjectResource($this->calculateService->get($project)),
                $request->project
            )
        );

        return response()->json(
            new ProjectResource($this->calculateService->get($project))
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Project  $project
     * @return JsonResponse
     */
    public function destroy(Project $project)
    {
        $project->delete();

        return response()->json(null, 204);
    }

    /**
     * Calculate project
     *
     * @return JsonResponse
     */
    public function calculate(Request $request)
    {
        return response()->json(
            new ProjectResource($this->calculateService->get($request->project))
        );
    }
}
