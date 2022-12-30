<?php

namespace App\Services\Project;

use App\Models\Price;
use App\Models\Project;
use App\Models\Step;
use Illuminate\Http\Request;

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

        foreach ([true, false] as $isClient) {
            foreach (self::TYPES as $code => $title) {
                Step::create([
                    'title'      => $title,
                    'code'       => $code,
                    'project_id' => $project->id,
                    'isClient'   => $isClient
                ]);
            }
        }

        Price::create([
            'project_id' => $project->id
        ]);

        return $project;
    }
}
