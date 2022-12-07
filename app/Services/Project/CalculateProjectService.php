<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Models\Task;

class CalculateProjectService
{
    const QA_COEFFICIENT = 0.2;

    private $types = [
        'analyst'  => 'Аналитика',
        'designer' => 'Дизайн',
        'front'    => 'Верстка',
        'back'     => 'Программинг',
        'qa'       => 'Тестирование',
        'content'  => 'Контент',
    ];

    private $steps = [
        'analyst'  => 'Аналитика',
        'designer' => 'Дизайн',
        'front'    => 'Верстка',
        'back'     => 'Программинг',
        'qa'       => 'Тестирование',
        'content'  => 'Контент',
        'buffer'   => 'Буфер по проекту',
    ];

    private $qa = ['front', 'back'];

    public function get (Project $project): Project
    {
        $this->getPrice($project);
        $this->getOptions($project);
        $this->getTasks($project);

        $this->setCalculatedTasks($project);
        $this->setQa($project);
        $this->setTotal($project);
        $this->setSteps($project);

        $project->agreementWeeks = 2;


//        $this->setSteps($project);
//        $this->getTotalPrice($project);

        return $project;
    }

    private function getPrice (&$project): void
    {
        $project->price = $project->price()->first();
    }

    private function getOptions (&$project): void
    {
        $project->options = $project->options()->get();

        foreach ($project->options as &$option) {
            $option->totalPrice = $option->quantity * $option->price;
        }
    }

    private function getTasks (&$project): void
    {
        $project->tasks = $project->tasks()->get();

        foreach ($project->tasks as &$task) {
            foreach ($task->getFillable() as $key) {
                if (strpos($key, 'hours') > 0) {
                    $newKey = str_replace('hours', 'price', $key);

                    $type = explode('_', $key)[0];

                    $task->$newKey = $task->$key * $project->price->$type;
                }
            }
        }
    }

    private function setCalculatedTasks (&$project): void
    {
        $calculatedTasks = [];
        foreach ($project->tasks as &$task) {
            $calculatedTasks[] = [
                'title'              => $task['title'],
                'description'        => $task['description'],
                'analyst_price'      => $task['analyst_price'],
                'designer_price_min' => $task['designer_price_min'],
                'designer_price_max' => $task['designer_price_max'],
                'front_price_min'    => $task['front_price_min'],
                'front_price_max'    => $task['front_price_max'],
                'back_price_min'     => $task['back_price_min'],
                'back_price_max'     => $task['back_price_max'],

                'analyst_hours'      => $task['analyst_hours'],
                'designer_hours_min' => $task['designer_hours_min'],
                'designer_hours_max' => $task['designer_hours_max'],
                'front_hours_min'    => $task['front_hours_min'],
                'front_hours_max'    => $task['front_hours_max'],
                'back_hours_min'     => $task['back_hours_min'],
                'back_hours_max'     => $task['back_hours_max'],
            ];
        }

        $project->calculated = $calculatedTasks;
    }

    private function setQa (&$project): void
    {
        $qa = [];
        foreach ($project->calculated as $calculatedTask) {
            foreach ($calculatedTask as $keyTask => $value) {
                foreach ($this->qa as $key) {
                    if (strpos($keyTask, $key) === 0) {

                        if (! array_key_exists($keyTask, $qa)) {
                            $qa[$keyTask] = 0;
                        }

                        $qa[$keyTask] += $value;
                    }
                }
            }
        }

        foreach ($qa as &$value) {
            $value = round($value * self::QA_COEFFICIENT, 2);
        }

        $project->qa = $qa;
    }

    private function setTotal (&$project): void
    {
        $total = [
            'step'    => [],
            'project' => []
        ];

        foreach ($this->types as $type => $title) {
            foreach ($project->calculated as $calculatedTask) {

                foreach ($calculatedTask as $key => $value) {
                    if (strpos($key, $type) === 0) {

                        if (! array_key_exists($key, $total['step'])) {
                            $total['step'][$key] = 0;
                        }

                        $total['step'][$key] += $value;
                    }
                }
            }
        }

        foreach ($total['step'] as $key => &$value) {
            if (array_key_exists($key, $project->qa)) {
                $value += $project->qa[$key];
            }
        }

        $total['project']['back_price_min'] = $total['step']['front_price_min'] + $total['step']['back_price_min'] +
            $total['step']['analyst_price'] + $total['step']['designer_price_min'];

        $total['project']['back_price_max'] = $total['step']['front_price_max'] + $total['step']['back_price_max'] +
            $total['step']['analyst_price'] + $total['step']['designer_price_max'];

        $project->total = $total;
    }

    private function setSteps (&$project): void
    {
        $step = [];

        foreach ($this->types as $type => $title) {
            foreach ($project->calculated as $calculatedTask) {
                foreach ($calculatedTask as $key => $value) {
                    $newKey = "{$type}_hours";

                    if (strpos($key, $newKey) === 0) {

                        if (! array_key_exists($key, $step)) {
                            $step[$key] = [
                                'title' => $title,
                                'hours' => 0
                            ];
                        }

                        $step[$key]['hours'] += $value;
                    }
                }
            }
        }

        $step['qa']['title'] = $this->steps['qa'];
        $step['qa']['hours'] = (float) $project->qa['front_hours_min'] + (float) $project->qa['back_hours_min'];

        $step['buffer']['title'] = $this->steps['buffer'];
        $step['buffer']['hours'] =
            (
                $this->getAverage(
                    $step['front_hours_max']['hours'] + $project->qa['front_hours_max'],
                    $step['front_hours_min']['hours'] + $project->qa['front_hours_min']
                ) -
                $this->getAverage(
                    $project->qa['front_hours_max'],
                    $project->qa['front_hours_min'],
                ) -
                $step['front_hours_min']['hours']
            )
            +
            (
                $this->getAverage(
                    $step['back_hours_max']['hours'] + $project->qa['back_hours_max'],
                    $step['back_hours_min']['hours'] + $project->qa['back_hours_min']
                ) -
                $this->getAverage($project->qa['back_hours_max'], $project->qa['back_hours_min']) -
                $step['back_hours_min']['hours']
            )
            +
            (
                $this->getAverage($project->qa['back_hours_max'], $project->qa['back_hours_min']) +
                $this->getAverage($project->qa['front_hours_max'], $project->qa['front_hours_min']) -
                ($project->qa['back_hours_min'] + $project->qa['front_hours_min'])
            )
        ;


        $newStep = [];
        foreach ($step as $key => $item) {
            if (strpos($key, 'max') > 0) {
                continue;
            }

            $newKey = explode('_', $key)[0];

            $newStep[$newKey] = $item;
        }

        $start = 0;
        foreach ($newStep as &$step) {
            $numberOfWeeks = round($step['hours'] / $project->hours_per_week);
            $step['weeks'] = $numberOfWeeks;
            $step['start'] = $start;

            $start += $numberOfWeeks;

            $step['end'] = $start;
        }

        $project->steps = $newStep;
    }

    private function getAverage($first, $second): float
    {
        return abs(($first + $second) / 2);
    }
}
