<?php

namespace App\Services\Project;

use App\Models\Price;
use App\Models\Project;
use App\Models\Revisions;
use App\Models\Step;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreateProjectService
{
    const TYPES = [
        'analyst'  => 'Аналитика',
        'designer' => 'Дизайн',
        'front'    => 'Верстка',
        'back'     => 'Программинг',
        'qa'       => 'Тестирование',
        'content'  => 'Контент',
        'buffer'   => 'Буфер по проекту',
    ];

    public function create (Request $request): Project
    {
        $project = Project::create($request->all());

        $revision = Revisions::create([
            'revisionable_type' => Project::class,
            'revision_id'       => $project->id,
            'user_id'           => Auth::id(),
        ]);

        foreach ([true, false] as $isClient) {
            foreach (self::TYPES as $code => $title) {
                Step::create([
                    'title'       => $title,
                    'code'        => $code,
                    'project_id'  => $project->id,
                    'isClient'    => $isClient,
                    'revision_id' => $revision->id,
                ]);
            }
        }

        Price::create([
            'project_id'  => $project->id,
            'revision_id' => $revision->id,
        ]);

        return $project;
    }
}
