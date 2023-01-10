<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Models\Step;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class CalculateProjectService
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

    /**
     * Project|Array $this->project
     */
    public function get ($project)
    {
        $this->project = $project;

        if (is_object($this->project)) {
            $this->getPrice();
            $this->getOptions();
        }

        if (is_array($this->project)) {
            $this->project = (object) $this->project;
            $this->project->price = (object) $this->project->price;
            $this->project->steps = collect($this->project->steps);
            $this->project->tasks = collect($this->project->tasks);
        }

        $this->setCalculatedTasks();
        $this->setQa();
        $this->setSteps();
        $this->setTotal();

        $this->setDuration();
        $this->prepareNumbers();

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

    private function setCalculatedTasks (): void
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

        $this->project->calculated = $calculatedTasks;
    }

    private function setQa (): void
    {
        $qa = [];

        foreach ($this->project->calculated as $calculatedTask) {
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

        $this->project->qa = $qa;
    }

    private function setSteps (): void
    {
        $clientSteps  = [];
        $companySteps = [];
        foreach ($this->project->steps as &$step) {
            $step['hours_min'] = 0;
            $step['hours_max'] = 0;
            $step['hours_avg'] = 0;

            foreach ($this->project->calculated as $calculatedTask) {
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

            if ($step['isClient']) {
                $clientSteps[] = $step;
            } else {
                $companySteps[] = $step;
            }
        }

        $this->project->client  = ['steps' => $this->calculateSteps($clientSteps, true)];
        $this->project->company = ['steps' => $this->calculateSteps($companySteps, false)];
    }

    private function setTotal (): void
    {
        $total = [
            self::STEP_TYPE_CLIENT => [
                'step'    => [],
                'project' => [],
                'price'   => 0
            ],
            self::STEP_TYPE_COMPANY => [
                'step'    => [],
                'project' => [],
                'price'   => 0
            ],
        ];

        foreach ($total as $stepTypeKey => &$values) {
            foreach ($this->project->$stepTypeKey['steps'] as $step) {
                foreach ($this->project->calculated as $calculatedTask) {
                    foreach ($calculatedTask as $key => $value) {
                        if (strpos($key, $this->getStepCode($step)) === 0) {

                            if (! array_key_exists($key, $values['step'])) {
                                $values['step'][$key] = 0;
                            }

                            $values['step'][$key] += (float) $value;
                        }
                    }
                }
            }

            foreach ($values['step'] as $key => &$value) {
                if (array_key_exists($key, $this->project->qa)) {
                    $value += $this->project->qa[$key];
                }
            }

            if (count($values) === 0) {
                foreach ($this->taskFields as $field) {
                    $values['step'][$field] = 0;
                }
            }

            $values['project']['back_price_min'] =
                $values['step']['front_price_min'] + $values['step']['back_price_min'] +
                $values['step']['analyst_price'] + $values['step']['designer_price_min'];

            $values['project']['back_price_max'] =
                $values['step']['front_price_max'] + $values['step']['back_price_max'] +
                $values['step']['analyst_price'] + $values['step']['designer_price_max'];

            $values['project']['back_price_min'] =
                $this->number_format($values['project']['back_price_min']);

            $values['project']['back_price_max'] =
                $this->number_format($values['project']['back_price_max']);

            $price = 0;
            foreach ($this->project->$stepTypeKey['steps'] as $step) {
                $price += (float) $step['price'];
            }

            $values['price'] = $this->number_format($price);
        }

        $this->project->total = $total;
    }

    private function setDuration ()
    {
        foreach ([self::STEP_TYPE_COMPANY, self::STEP_TYPE_CLIENT] as $stepType) {
            $duration = $durationInWeeks = 0;

            $end = $this->project->start;

            foreach ($this->project->$stepType['steps'] as $key => $step) {
                if (!in_array($this->getStepCode($step), ['buffer'])) {
                    $durationInWeeks += $step['weeks'] + $step['agreement'];
                }
            }

            if (self::STEP_TYPE_CLIENT === $stepType) {
                $durationInWeeks += (int) $this->project->client_buffer;
            }

            if ($durationInWeeks !== (float) 0) {
                $durationWithoutBuffer = $durationInWeeks - $this->filterStep( 'buffer')['weeks'];

                $end      = Carbon::create($this->project->start)->addWeeks($durationWithoutBuffer)->subDay()->format('d.m.Y');
                $duration = round((Carbon::create($end)->diffInDays($this->project->start)) / 7 / 4.5, 2);
            }

            $values = [
                'countWeeks' => $durationInWeeks,
                'duration'   => $duration,
                'start'      => Carbon::create($this->project->start)->format('Y-m-d'),
                'end'        => Carbon::create($end)->format('Y-m-d'),
                'steps'      => $this->project->$stepType['steps'],
            ];

            $this->project->$stepType = (object) $values;
        }
    }

    private function prepareNumbers (): void
    {
        $this->project->calculated = $this->prepare($this->project->calculated);
        $this->project->qa         = $this->prepare([$this->project->qa])[0];

        $this->project->client->steps  = $this->prepare($this->project->client->steps);
        $this->project->company->steps = $this->prepare($this->project->company->steps);
    }

    private function prepare (array $array): array
    {
        foreach ($array as &$item) {
            if (is_object($item)) {
                $item = $item->toArray();
            }

            foreach ($item as $key => &$value) {
                if (strpos($key, 'price') > 0 || $key === 'price') {
                    $value = $this->number_format($value);
                } elseif (strpos($key, 'hours') === 0) {
                    $value = round($value, 2);
                }
            }
        }

        return $array;
    }

    private function filterStep ($steps, string $code, bool $isClient = true)
    {
        $step = null;
        if (is_array($steps)) {
            foreach ($steps as $filtered) {
                if ($this->getStepCode($filtered) === $code && (bool) $filtered['isClient'] === $isClient) {
                    return $filtered;
                }
            }
        } else {
            $filtered = $steps->filter(function ($step) use ($code, $isClient) {
                return $this->getStepCode($step) === $code && (bool) $step['isClient'] === $isClient;
            });

            $step = $filtered->first();
        }

        return $step;
    }

    private function getAverage(float $first, float  $second): float
    {
        return abs(($first + $second) / 2);
    }

    private function number_format ($value): string
    {
        if (in_array($value, [0, '-'])) {
            return '-';
        }

        return number_format((float) $value, 2, '.', ' ');
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

    private function calculateSteps ($steps, bool $isClient)
    {
        $front  = $this->filterStep($steps, 'front', $isClient);
        $back   = $this->filterStep($steps, 'back', $isClient);
        $qa     = $this->filterStep($steps, 'qa', $isClient);
        $buffer = $this->filterStep($steps, 'buffer', $isClient);

        try {
            $qa['hours_min'] = (float) $this->project->qa['front_hours_min'] + (float) $this->project->qa['back_hours_min'];
            $qa['hours_max'] = (float) $this->project->qa['front_hours_max'] + (float) $this->project->qa['back_hours_max'];
            $qa['hours_avg'] = $this->getAverage(
                    (float) $this->project->qa['front_hours_min'], (float) $this->project->qa['front_hours_max']
                ) + $this->getAverage(
                    (float) $this->project->qa['back_hours_min'], (float) $this->project->qa['back_hours_max']
                );
        } catch (\Exception $exception) {
            $qa['hours_min'] = 0;
            $qa['hours_max'] = 0;
            $qa['hours_avg'] = 0;
            $this->project->qa = [
                'front_hours_min' => 0,
                'back_hours_min'  => 0,
                'front_hours_max' => 0,
                'back_hours_max'  => 0,
            ];
        }

        $buffer['hours_min'] =
            (
                $this->getAverage(
                    $front['hours_max'] + $this->project->qa['front_hours_max'],
                    $front['hours_min'] + $this->project->qa['front_hours_min']
                ) -
                $this->getAverage(
                    $this->project->qa['front_hours_max'],
                    $this->project->qa['front_hours_min'],
                ) -
                $front['hours_min']
            )
            +
            (
                $this->getAverage(
                    $back['hours_max'] + $this->project->qa['back_hours_max'],
                    $back['hours_min'] + $this->project->qa['back_hours_min']
                ) -
                $this->getAverage($this->project->qa['back_hours_max'], $this->project->qa['back_hours_min']) -
                $back['hours_min']
            )
            +
            (
                $this->getAverage($this->project->qa['back_hours_max'], $this->project->qa['back_hours_min']) +
                $this->getAverage($this->project->qa['front_hours_max'], $this->project->qa['front_hours_min']) -
                ($this->project->qa['back_hours_min'] + $this->project->qa['front_hours_min'])
            )
        ;

        $start = 0;
        foreach ($steps as &$step) {
            $stepCode = $this->getStepCode($step);

            $numberOfWeeks = round($step['hours_min'] / $this->project->hours_per_week);

            if ($isClient) {
                $numberOfWeeks = round($step['hours_avg'] / $this->project->hours_per_week);
            }

            $numberOfWeeks = floor($numberOfWeeks / $step['employee_quantity']);

            if (! $isClient && $stepCode === 'buffer') {
                $start -= $qa['agreement'];
            }

            $step['weeks'] = $numberOfWeeks;
            $step['start'] = $start - $step['parallels'];

            $start += $numberOfWeeks + $step['agreement'];

            $offsetWeeks = $start - $numberOfWeeks;
            $step['start_date'] = (Carbon::create($this->project->start)->addWeeks($offsetWeeks)->subDay())->format('d.m.Y');

            if ($offsetWeeks === (float) 0) {
                $step['start_date'] = (Carbon::create($this->project->start)->addWeeks($offsetWeeks))->format('d.m.Y');
            }

            $step['end_date'] = (Carbon::create($this->project->start)->addWeeks($start)->subDay())->format('d.m.Y');

            $step['end'] = $start;

            $hoursKey = ($isClient) ? 'hours_avg' : 'hours_min';

            $price = ($stepCode === 'buffer') ? $this->project->price->qa : $this->project->price->$stepCode;

            $step['price'] = round($step[$hoursKey] * $price, 2);
        }

        return $steps;
    }
}
