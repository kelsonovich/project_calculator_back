<?php

namespace App\Services\Project\Task;

use App\Models\Project;
use Illuminate\Http\Request;

class CreateTaskService
{
    public function create (Request $request)
    {
        $project = Project::find($request->project);

        return $project->tasks()->create($request->all());
    }
}
