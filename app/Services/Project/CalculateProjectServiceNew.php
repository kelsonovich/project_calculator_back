<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Models\Step;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class CalculateProjectServiceNew
{
    const QA_COEFFICIENT = 0.2;

    const STEP_TYPE_CLIENT  = 'client';
    const STEP_TYPE_COMPANY = 'company';

    private $qa = ['front', 'back'];

    private $taskFields = [
        'analyst_price',
        'designer_price_min',
        'designer_price_max',
        'front_price_min',
        'front_price_max',
        'back_price_min',
        'back_price_max',

        'analyst_hours',
        'designer_hours_min',
        'designer_hours_max',
        'front_hours_min',
        'front_hours_max',
        'back_hours_min',
        'back_hours_max',
    ];

    /** Project|Array $this->project */
    private $project;

    public function get ($project)
    {
        $this->project = $project;

        if (is_object($this->project)) {
            $this->getPrice();
            $this->getOptions();
        } elseif (is_array($this->project)) {
            $this->project = (object) $this->project;
            $this->project->price = (object) $this->project->price;
            $this->project->steps = collect($this->project->steps);
            $this->project->tasks = collect($this->project->tasks);
        }

        $this->project->calculated = [
            'tasks' => [],
            'qa'    => [],
            'steps' => [],
            'total' => [],
        ];

        $this->calculatedTasks();
        $this->setQa();
//        $this->setSteps();
//        $this->setTotal();
//
//        $this->setDuration();
//        $this->prepareNumbers();

        dd($this->project->calculated);

        return $this->project;
    }


    private function getPrice (): void
    {
        $this->project->price = $this->project->price()->first();
    }

    private function getOptions (): void
    {
        foreach ($this->project->options as &$option) {
            $option->totalPrice = $option->quantity * $option->price;
        }
    }

    private function calculatedTasks (): void
    {
        $calculatedTasks = [];
        foreach ($this->project->tasks as &$task) {
            $taskKeys = (is_array($task)) ? array_keys($task) : array_keys($task->toArray());

            foreach ($taskKeys as $key) {
                if (strpos($key, 'hours') > 0) {
                    $newKey = str_replace('hours', 'price', $key);

                    $type = explode('_', $key)[0];

                    $task[$newKey] = $task[$key] * $this->project->price->$type;
                }
            }

            $calculatedTask = [
                'id'          => $task['id'],
                'title'       => $task['title'],
                'description' => $task['description'],
            ];

            foreach ($this->taskFields as $field) {
                $calculatedTask[$field] = $task[$field];
            }

            $calculatedTasks[] = $calculatedTask;
        }

        $this->project->calculated['tasks'] = $calculatedTasks;
    }

    private function setQa (): void
    {
        $qa = [];
        foreach ($this->project->calculated['tasks'] as $calculatedTask) {
            foreach ($calculatedTask as $keyTask => $value) {
                foreach ($this->qa as $key) {
                    if (strpos($keyTask, $key) === 0) {

                        if (! array_key_exists($keyTask, $qa)) {
                            $qa[$keyTask] = 0;
                        }

                        $qa[$keyTask] += (float) $value;
                    }
                }
            }
        }

        foreach ($qa as &$value) {
            $value = round($value * self::QA_COEFFICIENT, 2);
        }

        unset($value);

        $this->project->calculated['qa'] = $qa;
    }
}
