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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    private CreateProjectService $createProjectService;
    private CalculateProjectService $calculateProjectService;

    public function __construct(
        CreateProjectService $createProjectService,
        CalculateProjectService $calculateProjectService
    )
    {
        $this->createProjectService = $createProjectService;
        $this->calculateProjectService = $calculateProjectService;
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
        $project = $this->createProjectService->create($request);

        return response()->json(new ProjectResource($project), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return JsonResponse
     */
    public function show(Project $project)
    {
        return response()->json(
            new ProjectResource($this->calculateProjectService->get($project))
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
        $project->update($request->all());

        return response()->json(
            new ProjectResource($this->calculateProjectService->get($project))
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
            new ProjectResource($this->calculateProjectService->get($request->project))
        );
    }
}
