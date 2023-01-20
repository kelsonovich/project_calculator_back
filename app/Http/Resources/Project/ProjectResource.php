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
                'client_buffer'  => $this->client_buffer,
                'revision_id'    => $this->revision_id,
                'parent_id'      => $this->parent_id,

                'calculated' => $this->calculated,
                'steps'      => $this->steps,

                'client'  => $this->client,
                'company' => $this->company,

                'calculatedOptions' => $this->calculatedOptions,
            ]
        ];
    }
}
