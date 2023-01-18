<?php

namespace App\Services\Project;

use App\Http\Resources\Project\ProjectResource;
use App\Models\Option;
use App\Models\Price;
use App\Models\Project;
use App\Models\Revisions;
use App\Models\Step;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class UpdateProjectService
{
    public function update (ProjectResource $oldProject, array $newProject)
    {
        $revision[Task::class]    = Changes::get(Task::class, $oldProject->tasks, $newProject['tasks']);
        $revision[Step::class]    = Changes::get(Step::class, $oldProject->steps, $newProject['steps']);
        $revision[Option::class]  = Changes::get(Option::class, $oldProject->options, $newProject['options']);
        $revision[Project::class] = Changes::get(Project::class, collect([$oldProject]), [$newProject]);

        $revision[Price::class] = Changes::get(
            Price::class,
            collect([$oldProject->price]),
            [$newProject['price']]
        );

        if ($this->updateIsNeeded($revision)) {
            $revisionModel = Revisions::create([
                'revisionable_type' => Project::class,
                'revision_id'       => $oldProject->id,
                'user_id'           => Auth::id(),
            ]);

            $this->createNewModel($revisionModel, Project::class, $oldProject);
        }

        dd(1);

        return $project;
    }

    /** Проверяем необходимость логирования изменений */
    /** Может просто отправили */
    private function updateIsNeeded (array $revisions): bool
    {
        $updateIsNeeded = false;

        foreach ($revisions as $revision) {
            foreach ($revision as $value) {
                if (count($value) > 0) {
                    return true;
                }
            }
        }

        return $updateIsNeeded;
    }

    /** Создаем новую модель и записываем ей HASH-изменений */
    private static function createNewModel (Revisions $revisionModel, string $entity, $oldModel): void
    {
        $model = $entity::find($oldModel['id']);
        $newModel = $model->replicate();

        $newModel->revision_id = $revisionModel->id;

        if (is_null($newModel->parent_id)) {
            $newModel->parent_id = $oldModel->id;
        }

        $newModel->save();
    }

    /** Проверяем и устанавливаем изменения в каждой модели */
    private function setChanges (): void
    {

    }
}
