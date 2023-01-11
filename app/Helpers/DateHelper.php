<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    public static function formattingForProject ($start, $addWeeks, $isSubDay = false): string
    {
        $date = Carbon::create($start)->addWeeks($addWeeks);

        if ($isSubDay) {
            $date = $date->subDay();
        }

        return $date->format('d.m.Y');
    }
}
