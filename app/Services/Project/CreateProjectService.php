<?php

namespace App\Services\Project;

use App\Models\Price;
use App\Models\Project;
use Illuminate\Http\Request;

class CreateProjectService
{
    public function create (Request $request): Project
    {
        $project = Project::create($request->all());

        Price::create([
            'project_id' => $project->id
        ]);

        return $project;
    }
}
