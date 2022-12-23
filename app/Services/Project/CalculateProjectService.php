<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Models\Step;
use App\Models\Task;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Database\Eloquent\Collection;

class CalculateProjectService
{
    const QA_COEFFICIENT = 0.2;

    const AGREEMENT_FOR = 2;

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

    /**
     * Project|Array $project
     */
    public function get ($project)
    {
        if (is_object($project)) {
            $this->getPrice($project);
            $this->getOptions($project);
            $this->getTasks($project);
        }

        if (is_array($project)) {
            $project = (object) $project;
            $project->price = (object) $project->price;
            $project->steps = collect($project->steps);
        }

        $this->setCalculatedTasks($project);
        $this->setQa($project);
        $this->setSteps($project);
        $this->setTotal($project);

        $project->agreementWeeks = self::AGREEMENT_FOR;

        $this->setDuration($project);
        $this->prepareNumbers($project);

        return $project;
    }

    private function getPrice (&$project): void
    {
        $project->price = $project->price()->first();
    }

    private function getOptions (&$project): void
    {
        foreach ($project->options as &$option) {
            $option->totalPrice = $option->quantity * $option->price;
        }
    }

    private function getTasks (&$project): void
    {
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

                        $qa[$keyTask] += (float) $value;
                    }
                }
            }
        }

        foreach ($qa as &$value) {
            $value = round($value * self::QA_COEFFICIENT, 2);
        }

        $project->qa = $qa;
    }

    private function setSteps (&$project): void
    {
        foreach ($project->steps as &$step) {
            $step['hours_min'] = 0;
            $step['hours_max'] = 0;
            $step['hours_avg'] = 0;
        }

        foreach ($project->steps as &$step) {
            foreach ($project->calculated as $calculatedTask) {
                foreach ($calculatedTask as $key => $value) {

                    $newKey = $this->getStepCode($step);

                    $newKeyHours = "{$newKey}_hours";

                    if ($key === $newKeyHours) {
                        $step['hours_avg'] += $value;
                    } elseif (strpos($key, $newKeyHours) === 0) {
                        $step[str_replace($newKey . '_', '', $key)] += $value;
                    }
                }
            }
        }

        foreach ($project->steps as &$step) {
            $step['hours_avg'] = $step['hours_max'] - $step['hours_min'];
        }

        $front  = $this->filterStep($project, 'front');
        $back   = $this->filterStep($project, 'back');
        $qa     = $this->filterStep($project, 'qa');
        $buffer = $this->filterStep($project, 'buffer');


        try {
            $qa['hours_min'] = (float) $project->qa['front_hours_min'] + (float) $project->qa['back_hours_min'];
        } catch (\Exception $exception) {
            $qa['hours_min'] = 0;
            $project->qa = [
                'front_hours_min' => 0,
                'back_hours_min'  => 0,
                'front_hours_max' => 0,
                'back_hours_max'  => 0,
            ];
        }

        $buffer['hours_min'] =
            (
                $this->getAverage(
                    $front['hours_max'] + $project->qa['front_hours_max'],
                    $front['hours_min'] + $project->qa['front_hours_min']
                ) -
                $this->getAverage(
                    $project->qa['front_hours_max'],
                    $project->qa['front_hours_min'],
                ) -
                $front['hours_min']
            )
            +
            (
                $this->getAverage(
                    $back['hours_max'] + $project->qa['back_hours_max'],
                    $back['hours_min'] + $project->qa['back_hours_min']
                ) -
                $this->getAverage($project->qa['back_hours_max'], $project->qa['back_hours_min']) -
                $back['hours_min']
            )
            +
            (
                $this->getAverage($project->qa['back_hours_max'], $project->qa['back_hours_min']) +
                $this->getAverage($project->qa['front_hours_max'], $project->qa['front_hours_min']) -
                ($project->qa['back_hours_min'] + $project->qa['front_hours_min'])
            )
        ;

        $start = 0;
        foreach ($project->steps as &$step) {
            $numberOfWeeks = round($step['hours_min'] / $project->hours_per_week);
            $step['weeks'] = $numberOfWeeks;
            $step['start'] = $start;

            $start += $numberOfWeeks;

            $step['end'] = $start;

            $stepCode = $this->getStepCode($step);

            $step['price'] = round(
                $step['hours_min'] * (($stepCode === 'buffer')
                    ? $project->price->qa
                    : $project->price->$stepCode),
                2
            );
        }
    }

    private function setTotal (&$project): void
    {
        $total = [
            'step'    => [],
            'project' => []
        ];

        foreach ($project->steps as $step) {
            foreach ($project->calculated as $calculatedTask) {
                foreach ($calculatedTask as $key => $value) {
                    if (strpos($key, $this->getStepCode($step)) === 0) {

                        if (! array_key_exists($key, $total['step'])) {
                            $total['step'][$key] = 0;
                        }

                        $total['step'][$key] += (float) $value;
                    }
                }
            }
        }

        foreach ($total['step'] as $key => &$value) {
            if (array_key_exists($key, $project->qa)) {
                $value += $project->qa[$key];
            }
        }

        if (count($total['step']) === 0) {
            foreach ($this->taskFields as $field) {
                $total['step'][$field] = 0;
            }
        }

        $total['project']['back_price_min'] = $total['step']['front_price_min'] + $total['step']['back_price_min'] +
            $total['step']['analyst_price'] + $total['step']['designer_price_min'];

        $total['project']['back_price_max'] = $total['step']['front_price_max'] + $total['step']['back_price_max'] +
            $total['step']['analyst_price'] + $total['step']['designer_price_max'];

        $total['project']['back_price_min'] = $this->number_format($total['project']['back_price_min']);
        $total['project']['back_price_max'] = $this->number_format($total['project']['back_price_max']);

        $clientPrice = 0;
        foreach ($project->steps as $step) {
            $clientPrice += (float) $step['price'];
        }

        $total['price'] = $this->number_format($clientPrice);

        $project->total = $total;
    }

    private function setDuration ($project)
    {
        $duration = 0;
        $durationInWeeks = 0;

        $end = $project->start;

        foreach ($project->steps as $key => $step) {
            if ($key !== 'qa') {
                $durationInWeeks += $step['weeks'];
            }
        }

        if ($durationInWeeks !== (float) 0) {
            $durationWithoutBuffer = $durationInWeeks - $this->filterStep($project, 'buffer')['weeks'];

            $end      = Carbon::create($project->start)->addWeeks($durationWithoutBuffer + self::AGREEMENT_FOR)->subDay()->format('d.m.Y');
            $duration = round((Carbon::create($end)->diffInDays($project->start)) / 7 / 4.5, 2);
        }

        $project->countWeeks = $durationInWeeks;
        $project->duration   = $duration;
        $project->start      = Carbon::create($project->start)->format('Y-m-d');
        $project->end        = Carbon::create($end)->format('Y-m-d');
    }

    private function prepareNumbers ($project): void
    {
        $project->calculated = $this->prepare($project->calculated);
        $project->qa         = $this->prepare([$project->qa])[0];

        foreach ($project->steps as &$step) {
            $step['price']     = $this->number_format((float) $step['price']);
            $step['hours_min'] = round((float) $step['hours_min'], 2);
        }
    }

    private function prepare(array $array): array
    {
        foreach ($array as &$task) {
            foreach ($task as $key => &$value) {
                if (strpos($key, 'price') > 0) {
                    $value = $this->number_format($value);
                }
            }
        }

        return $array;
    }

    private function filterStep (object $project, string $code)
    {
        $filtered = $project->steps->filter(function ($step) use ($code) {
            return $this->getStepCode($step) === $code;
        });

        return $filtered->first();
    }

    private function getAverage(float $first, float  $second): float
    {
        return abs(($first + $second) / 2);
    }

    private function number_format (float $value): string
    {
        if ($value == 0) {
            return '-';
        }

        return number_format($value, 2, '.', ' ');
    }

    private function getStepCode ($step): string
    {
        try {
            $code = $step->code;
        } catch (\Exception $exception) {
            $code = $step['code'];
        }

        return $code;
    }

}
