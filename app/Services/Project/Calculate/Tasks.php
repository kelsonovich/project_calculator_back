<?php

namespace App\Services\Project\Calculate;

/** Класс для расчета задач */
class Tasks
{
    const QA_COEFFICIENT = 0.2;

    private static $prjectTasks = [];

    private static array $tasks = [];
    private static array $qa    = [];
    private static array $steps = [];
    private static array $total = [];

    private static object $price;
    private static array $fields;
    private static array $qaFields = ['front', 'back'];

    public static function calculate ($tasks, $price, $fields): array
    {
        self::$prjectTasks = $tasks;
        self::$price       = $price;
        self::$fields      = $fields;

        self::tasks();
        self::qa();
        self::steps();
        self::total();

        return [
            'tasks' => self::$tasks,
            'qa'    => self::$qa,
            'steps' => self::$steps,
            'total' => self::$total,
        ];
    }

    /** Рассчитываем стоимость работ в каждой задаче */
    private static function tasks (): void
    {
        foreach (self::$prjectTasks as &$task) {
            $taskKeys = (is_array($task)) ? array_keys($task) : array_keys($task->toArray());

            foreach ($taskKeys as $key) {
                if (strpos($key, 'hours') > 0) {
                    $newKey = str_replace('hours', 'price', $key);

                    $type = explode('_', $key)[0];

                    $task[$newKey] = $task[$key] * self::$price->$type;
                }
            }

            $calculatedTask = [
                'id'          => $task['id'] ?? null,
//                'id'          => $task['id'],
                'title'       => $task['title'],
                'description' => $task['description'],
                'sort'        => $task['sort'],
            ];

            foreach (self::$fields as $field) {
                $calculatedTask[$field] = $task[$field];
            }

            self::$tasks[] = $calculatedTask;
        }
    }

    /** Рассчитываем стоимость тестирование */
    private static function qa (): void
    {
        foreach (self::$tasks as $calculatedTask) {
            foreach ($calculatedTask as $keyTask => $value) {
                foreach (self::$qaFields as $key) {
                    if (strpos($keyTask, $key) === 0) {

                        if (! array_key_exists($keyTask, self::$qa)) {
                            self::$qa[$keyTask] = 0;
                        }

                        self::$qa[$keyTask] += round((float) $value * self::QA_COEFFICIENT, 2);
                    }
                }
            }
        }
    }

    /** Рассчитываем всего часов и времени по этапно */
    private static function steps (): void
    {
        $tasks = array_merge(self::$tasks, [self::$qa]);
        foreach ($tasks as $task) {
            foreach (self::$fields as $field) {
                if (array_key_exists($field, $task)) {

                    if (! array_key_exists($field, self::$steps)) {
                        self::$steps[$field] = 0;
                    }

                    self::$steps[$field] += (float) $task[$field];
                }
            }
        }
    }

    /** Рассчитываем время на разработку проекта минимум / максимум */
    private static function total (): void
    {
        self::$total = [
            'back_price_min' => 0,
            'back_price_max' => 0,
        ];

        foreach (self::$total as $key => &$value) {
            $newKey = explode('_', $key);
            $newKey = end($newKey);

            foreach (self::$fields as $field) {
                if (strpos($field, 'price') > 0) {
                    if (strpos($field, $newKey) > 0) {
                        $value += (array_key_exists($field, self::$steps)) ? (float) self::$steps[$field] : 0;
                    }
                }
            }

            $value += (array_key_exists('analyst_price', self::$steps)) ? (float) self::$steps['analyst_price'] : 0;
        }
    }
}
