<?php

namespace App\Services\Project\Calculate;

use App\Helpers\DateHelper;
use Carbon\Carbon;

/** Класс для расчета задач */
class Steps
{
    private static $tasks;

    private static array $client  = [];
    private static array $company = [];
    private static object $project;

    public static function calculate (object $project): array
    {
        self::$project = $project;
        self::$tasks   = self::$project->calculated;

        return self::steps(self::$project->steps);
    }

    private static function steps($steps): array
    {
        $tasks = array_merge(self::$tasks['tasks'], [self::$tasks['qa']]);

        foreach ($steps as $step) {
            $step['hours_min'] = 0;
            $step['hours_max'] = 0;
            $step['hours_avg'] = 0;

            $isClient = self::isClient($step);

            $code = self::getCode($step);

            if ($code === 'qa') {
                foreach (self::$tasks['qa'] as $key => $taskQa) {
                    if (strpos($key, '_hours') > 0) {
                        $newKey = substr($key, strpos($key, '_') + 1);

                        $step[$newKey] += $taskQa;
                    }
                }
            } else {
                foreach ($tasks as $task) {
                    foreach ($task as $key => $value) {
                        $newKeyHours = "{$code}_hours";

                        if ($key === 'analyst_hours') {
                            $step['hours_avg'] += $value;
                        } elseif (strpos($key, $newKeyHours) === 0) {
                            $step[str_replace($code . '_', '', $key)] += $value;
                        }
                    }
                }
            }


            if ($code !== 'analyst') {
                $hoursKeyMin = "{$code}_hours_min";
                $hoursKeyMax = "{$code}_hours_max";

                $qaHoursMin = (array_key_exists($hoursKeyMin, self::$tasks['qa']))
                    ? (float) self::$tasks['qa'][$hoursKeyMin]
                    : 0;

                $qaHoursMax = (array_key_exists($hoursKeyMax, self::$tasks['qa']))
                    ? (float) self::$tasks['qa'][$hoursKeyMax]
                    : 0;

                /** Разные варианты расчета для клинета и для компании */
                if ($isClient) {

                    $step['hours_avg'] = (self::getAverage([(float) $step['hours_max'], (float) $step['hours_min']])) -
                        (self::getAverage([(float) $qaHoursMax, (float) $qaHoursMin]));
                } else {
                    $step['hours_avg'] = (float) $step['hours_min'] - $qaHoursMin;
                }
            }

            if ($step['isClient']) {
                $client[] = $step;
            } else {
                $company[] = $step;
            }
        }

        self::$client  = ['steps' => self::calculateSteps($client ?? [], true)];
        self::$company = ['steps' => self::calculateSteps($company ?? [], false)];

        return [self::$client, self::$company];
    }

    private static function calculateSteps ($steps, bool $isClient)
    {
        $qa = self::filterStep($steps, 'qa', $isClient);

        $steps = self::calculateBuffer($steps, $qa, $isClient);

        $lastStepDuration  = 0;
        foreach ($steps as &$step) {
            $stepCode = self::getCode($step);

            /** Длительность этапа в неделях исходя из количества часов */
            $durationOnWeek = round($step['hours_avg'] / self::$project->hours_per_week);
            $durationOnWeek = floor($durationOnWeek / $step['employee_quantity']);

            /** Если это буффер для компании, то смещаем начало назад */
            if (! $isClient && $stepCode === 'buffer') {
                $lastStepDuration -= $qa['agreement'];
            }

            /** Смещаем начало этапа на количество запаралелленых недель */
            $step['start'] = $lastStepDuration - $step['parallels'];

            /** Количество недель, которые длился этап */
            $step['end']   = $step['start'] + $durationOnWeek + $step['agreement'];

            /** Переменная длительность этапа */
            $step['weeks'] = $durationOnWeek;

            $lastStepDuration += $durationOnWeek + $step['agreement'];

            /** Даты начала/завершения этапов */
            $step['start_date'] = DateHelper::formattingForProject(self::$project->start, $step['start']);
            $step['end_date'] = DateHelper::formattingForProject(self::$project->start, ($step['end'] - $step['agreement']), true);

            $price = ($stepCode === 'buffer') ? self::$project->price->qa : self::$project->price->$stepCode;

            $step['price'] = round($step['hours_avg'] * $price, 2);
        }

        return $steps;
    }

    /** Получаем этап по коду работ */
    public static function filterStep ($steps, string $code, bool $isClient = true)
    {
        $step = null;
        if (is_array($steps)) {
            foreach ($steps as $filtered) {
                if (self::getCode($filtered) === $code && (bool) $filtered['isClient'] === $isClient) {
                    return $filtered;
                }
            }
        } else {
            $filtered = $steps->filter(function ($step) use ($code, $isClient) {
                return self::getCode($step) === $code && (bool) $step['isClient'] === $isClient;
            });

            $step = $filtered->first();
        }

        return $step;
    }

    /** Получение кода этапа */
    public static function getCode ($step): string
    {
        try {
            $code = $step->code;
        } catch (\Exception $exception) {
            $code = $step['code'];
        }

        return $code;
    }

    /** Нахождение среднего арефметического */
    private static function getAverage(array $values): float
    {
        return (float) (array_sum($values) / count($values));
    }

    /** Проверяем является ли этап для клиента или для компании */
    private static function isClient ($step): bool
    {
        return (bool) $step['isClient'];
    }

    /** Устанавливаем новое значение этапа */
    private static function setStep ($steps, $replace)
    {
        foreach ($steps as &$step) {
            if ($step['id'] === $replace['id']) {
                $step = $replace;

                break;
            }
        }

        return $steps;
    }

    /** Высчитываем буффер для компании */
    private static function calculateBuffer ($steps, $qa, bool $isClient)
    {
        $front  = self::filterStep($steps, 'front', $isClient);
        $back   = self::filterStep($steps, 'back', $isClient);
        $buffer = self::filterStep($steps, 'buffer', $isClient);

        if (! $isClient) {
            try {
                $buffer['hours_avg'] =
                    (
                        self::getAverage([$front['hours_max'], $front['hours_min']])
                        -
                        self::getAverage([self::$tasks['qa']['front_hours_max'], self::$tasks['qa']['front_hours_min']])
                        -
                        $front['hours_avg']
                    )
                    +
                    (
                        self::getAverage([$back['hours_max'], $back['hours_min']])
                        -
                        self::getAverage([self::$tasks['qa']['back_hours_max'], self::$tasks['qa']['back_hours_min']])
                        -
                        $back['hours_avg']
                    )
                    +
                    (
                        self::getAverage([self::$tasks['qa']['front_hours_max'], self::$tasks['qa']['front_hours_min']])
                        +
                        self::getAverage([self::$tasks['qa']['back_hours_max'], self::$tasks['qa']['back_hours_min']])
                        -
                        $qa['hours_avg']
                    )
                ;
            } catch (\Exception $exception) {
                $buffer['hours_avg'] = 0;
            }

            $steps = self::setStep($steps, $buffer);
        }

        return $steps;
    }
}
