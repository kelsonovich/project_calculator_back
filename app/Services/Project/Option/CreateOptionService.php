<?php

namespace App\Services\Project\Option;

use App\Models\Project;
use Illuminate\Http\Request;

class CreateOptionService
{
    public function create (Request $request)
    {
        $project = Project::find($request->project_id);

        return $project->options()->create($request->all());
    }
}
