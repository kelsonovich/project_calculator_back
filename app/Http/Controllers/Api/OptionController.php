<?php

namespace App\Http\Controllers\Api;

use App\Models\Option;
use App\Services\Project\Option\CreateOptionService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OptionController extends Controller
{
    private CreateOptionService $createOptionService;

    public function __construct(CreateOptionService $createOptionService)
    {
        $this->createOptionService = $createOptionService;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $option = $this->createOptionService->create($request);

        return response()->json([
            'option' => $option,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Option $option
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Option $option)
    {
        $option->delete();

        return response()->json(null, 240);
    }
}
