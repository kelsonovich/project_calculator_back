<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'status' => true,
            'result' => [
                'id'             => $this->id,
                'title'          => $this->title,
                'description'    => $this->description,
                'start'          => $this->start,
                'end'            => $this->end,
                'hours_per_week' => $this->hours_per_week,
                'price'          => $this->price,
                'options'        => $this->options,
                'tasks'          => $this->tasks,
//                'duration'       => $this->duration,
//                'countWeeks'     => $this->countWeeks,
                'client_buffer'  => $this->client_buffer,

                'calculated'      => $this->calculated,
//                'calculatedSteps' => $this->calculatedSteps,
                'qa'              => $this->qa,
                'total'           => $this->total,
                'steps'           => $this->steps,

                'client'  => $this->client,
                'company' => $this->company,
            ]
        ];
    }
}
