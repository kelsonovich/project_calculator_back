<?php

namespace App\Services\Project;

//use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection;

class Changes
{
    const COMPARE_FIELDS = [
        \App\Models\Step::class => [
            'title', 'description', 'employee_quantity', 'agreement', 'parallels', 'sort'
        ],
        \App\Models\Task::class => [
            'title', 'description', 'analyst_hours', 'designer_hours_min', 'designer_hours_max',
            'front_hours_min', 'front_hours_max', 'back_hours_min', 'back_hours_max', 'sort',
        ],
        \App\Models\Option::class => [
            'title', 'description', 'quantity', 'price',
        ],
        \App\Models\Price::class => [
            'analyst', 'designer', 'front', 'back', 'qa', 'content',
        ],
        \App\Models\Project::class => [
            'title', 'description', 'start', 'hours_per_week', 'client_buffer',
        ],
    ];

    const COLLECTION_TO_COMPARE = [
        \App\Models\Step::class,
        \App\Models\Task::class,
        \App\Models\Option::class,
    ];

    public static function get (string $entity, Collection $oldModels, array $newModels): array
    {
        $revision = [
            'new'     => [],
            'removed' => [],
            'changes' => [],
        ];

        $models = self::prepare($newModels);

        /** Если проверяем изменения в отдельных сущностях */
        if (in_array($entity, self::COLLECTION_TO_COMPARE)) {
            $revision['new'] = $models['new'];

            $revision['removed'] = self::checkRemoved($oldModels, $models['models']);
        }

        $revision['changes'] = self::compare($entity, $oldModels, $models['models']);

        return $revision;
    }

    /** Check new entities */
    private static function prepare (array $newModels): array
    {
        $result = [
            'models' => [],
            'new'    => [],
        ];

        foreach ($newModels as $newModel) {
            if (array_key_exists('id', $newModel) && ! is_null($newModel['id']) ) {
                $result['models'][(int) $newModel['id']] = $newModel;
            } else {
                $result['new'][] = $newModel;
            }
        }

        return $result;
    }

    /** Get id's removed entities */
    private static function checkRemoved (Collection $oldModels, array $newModels): array
    {
        $removed = [];
        foreach ($oldModels as $model) {
            if (! array_key_exists($model->id, $newModels)) {
                $removed[] = $model->id;
            }
        }

        return $removed;
    }

    /** Compare entities on change */
    private static function compare (string $entity, Collection $oldModels, array $newModels): array
    {
        $changes = [];
        foreach ($oldModels as $oldModel) {
            if (! array_key_exists((int) $oldModel->id, $newModels)) {
                continue;
            }

            $newModel = $newModels[(int) $oldModel->id];
            foreach (self::COMPARE_FIELDS[$entity] as $key) {
                if (! array_key_exists($key, $newModel)) {
                    continue;
                }

                if ($oldModel->$key != $newModel[$key]) {
                    $changes[] = [
                        'action'            => \App\Models\RevisionLog::ACTION_UPDATE,
                        'revisionable_type' => $entity,
                        'model_id'          => $oldModel->id,
                        'key'               => $key,
                        'old_value'         => $oldModel[$key],
                        'new_value'         => $newModel[$key],
                    ];
                }
            }
        }

        return $changes;
    }
}
