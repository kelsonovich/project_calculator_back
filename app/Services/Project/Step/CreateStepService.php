<?php

namespace App\Services\Project\Step;

use App\Models\Project;
use Illuminate\Http\Request;

class CreateStepService
{
    public function createForProject (Request $request)
    {
        $project = Project::find($request->project);

        return $project->steps()->create($request->all());
    }
}
