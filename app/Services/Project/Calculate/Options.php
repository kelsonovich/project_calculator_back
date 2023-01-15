<?php

namespace App\Services\Project\Calculate;

use App\Helpers\DateHelper;
use Carbon\Carbon;

/** Класс для расчета задач */
class Options
{
    private static object $project;

    public static function calculate (object $project): array
    {
        self::$project = $project;

        return self::options(self::$project->options);
    }

    public static function options ($options): array
    {
        $calculated = [];
        foreach ($options as $option) {
            $option['total_price'] = $option['price'] * $option['quantity'];

            $calculated[] = $option;
        }

        return $calculated;
    }
}
