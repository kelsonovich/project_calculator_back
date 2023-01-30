<?php

namespace App\Services\Project;

use App\Helpers\DateHelper;
use App\Services\Project\Calculate\Options;
use App\Services\Project\Calculate\Steps;
use App\Services\Project\Calculate\Tasks;
use Carbon\Carbon;

class CalculateProjectService
{
    const TYPE_CLIENT  = 'client';
    const TYPE_COMPANY = 'company';

    private array $taskFields = [
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

    private float $sumOptions = 0;

    /** Project|Array $this->project */
    private $project;

    /**
     * @var array[]
     */
    private array $calculated;

    public function get ($project)
    {
        $this->project = $project;

        /** TO DO */
        $this->project->clientCompany = $this->project->client;
        $this->project->innerCompany  = $this->project->company;
        [$this->project->client, $this->project->company] = [null, null];

        if (is_array($this->project)) {
            $this->project = (object) $this->project;
            $this->project->price = (object) $this->project->price;
            $this->project->steps = collect($this->project->steps);
            $this->project->tasks = collect($this->project->tasks);

            $this->project->options = collect($this->project->options);
        }

        /** Расчеты, связанные с задачами */
        $this->project->calculated = Tasks::calculate(
            $this->project->tasks,
            $this->project->price,
            $this->taskFields
        );

        $this->project->steps->sortBy('sort');

        [$this->project->client, $this->project->company] = Steps::calculate($this->project);

        $this->project->calculatedOptions = Options::calculate($this->project);

        $this->prepareOptions();

        $this->setDuration();

        $this->prepareNumbers();

        return $this->project;
    }

    private function setDuration (): void
    {
        foreach ([self::TYPE_COMPANY, self::TYPE_CLIENT] as $stepType) {
            $duration = $durationInWeeks = 0;

            $end = $this->project->start;

            $steps = $this->project->$stepType['steps'];

            $price = 0;
            foreach ($steps as $step) {
                if (! in_array(Steps::getCode($step), ['buffer'])) {
                    $durationInWeeks += $step['weeks'] + $step['agreement'];
                }

                $price += (float) $step['price'];
            }

            if (self::TYPE_CLIENT === $stepType) {
                $durationInWeeks += (int) $this->project->client_buffer;
            }

            if ($durationInWeeks !== (float) 0) {
//                $durationWithoutBuffer = $durationInWeeks - Steps::filterStep($steps, 'buffer', (self::TYPE_CLIENT === $stepType))['weeks'];

                $end      = DateHelper::formattingForProject($this->project->start, $durationInWeeks, true);
                $duration = round((Carbon::create($this->project->start)->diffInDays($end)) / 7 / 4.5, 2);
            }

            $values = [
                'price'      => ($price + $this->sumOptions),
                'countWeeks' => $durationInWeeks,
                'duration'   => $duration,
                'start'      => DateHelper::formattingForProject($this->project->start, 0),
                'end'        => DateHelper::formattingForProject($end, 0),
                'steps'      => $this->project->$stepType['steps'],
            ];

            $this->project->$stepType = (object) $values;
        }
    }

    private function prepareOptions (): void
    {
        $options = $this->project->calculatedOptions;

        foreach ($options as &$option) {
            $this->sumOptions += (float) $option['total_price'];
        }

        $this->project->calculatedOptions = $options;
    }

    /** Форматирование цены */
    private function number_format ($value): string
    {
        if (in_array($value, [0, '-'])) {
            return '-';
        }

        return number_format((float) $value, 2, '.', ' ');
    }

    private function prepareNumbers(): void
    {
        $arrayTasks = $this->project->calculated;

        $arrayTasks['tasks'] = $this->prepare($arrayTasks['tasks']);
        $arrayTasks['qa']    = $this->prepare([$arrayTasks['qa']]);
        $arrayTasks['steps'] = $this->prepare([$arrayTasks['steps']]);
        $arrayTasks['total'] = $this->prepare([$arrayTasks['total']]);

        $this->project->calculated = $arrayTasks;

        $this->project->client->steps  = $this->prepare($this->project->client->steps);
        $this->project->company->steps = $this->prepare($this->project->company->steps);

        $this->project->client->price  = $this->number_format($this->project->client->price);
        $this->project->company->price = $this->number_format($this->project->company->price);

        $this->project->client->start = DateHelper::formatProjectDate($this->project->client->start);
        $this->project->client->end   = DateHelper::formatProjectDate($this->project->client->end);

        $this->project->company->start = DateHelper::formatProjectDate($this->project->company->start);
        $this->project->company->end   = DateHelper::formatProjectDate($this->project->company->end);
    }

    private function prepare ($array): array
    {
        foreach ($array as &$item) {
            if (is_object($item)) {
                $item = $item->toArray();
            }

            foreach ($item as $key => &$value) {
                if (strpos($key, 'price') > 0 || $key === 'price') {
                    $value = $this->number_format($value);
                } elseif (strpos($key, 'hours') === 0 || strpos($key, '_hours') > 0) {
                    $value = round($value, 2);
                }
            }
        }

        return $array;
    }
}
