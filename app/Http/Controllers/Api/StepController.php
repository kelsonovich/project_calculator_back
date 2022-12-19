<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\Step\UpdateStepRequest;
use App\Models\Step;
use App\Services\Project\Step\CreateStepService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StepController extends Controller
{
    private CreateStepService $createStepService;

    public function __construct(CreateStepService $createStepService)
    {
        $this->createStepService = $createStepService;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $steps = $this->createStepService->createForProject($request);

        return response()->json([
            'step' => $steps,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateStepRequest $request
     * @param \App\Models\Step $step
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateStepRequest $request, Step $step)
    {
        $step->update($request->all());

        return response()->json([
            'step' => $step,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Step  $step
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Step $step)
    {
        $step->delete();

        return response()->json(null, 204);
    }
}
