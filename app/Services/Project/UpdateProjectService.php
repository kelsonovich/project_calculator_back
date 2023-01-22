<?php

namespace App\Services\Project;

use App\Http\Resources\Project\ProjectResource;
use App\Models\Option;
use App\Models\Price;
use App\Models\Project;
use App\Models\RevisionLog;
use App\Models\Revisions;
use App\Models\Step;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class UpdateProjectService
{
    private Project $project;
    private array $revision;
    private array $values;

    public function update (Project $oldProject, array $newProject): Project
    {
        $this->project = $oldProject;

        $this->values = $this->setValues($newProject);
        foreach ($this->values as $entity => $values) {
            $this->revision[$entity] = Changes::get($entity, $values['old'], $values['new']);
        }

        if ($this->updateIsNeeded()) {
            $revisionModel = Revisions::create([
                'parent_id'         => $this->project->revision_id,
                'revisionable_type' => Project::class,
                'revision_id'       => $this->project->id,
                'user_id'           => Auth::id(),
            ]);

            $project = $this->createNewProject($revisionModel);

            foreach ($this->revision as $entity => $changes) {
                $this->applyRevision($revisionModel, $entity, $changes);
            }
        }

        return $project ?? $oldProject;
    }

    /** Формируем массив соответствий моделей -> старых данных -> новых данных */
    private function setValues (array $newProject): array
    {
        return [
            Project::class => ['old' => collect([$this->project]),        'new' => [$newProject]],
            Task::class    => ['old' => $this->project->tasks,            'new' => $newProject['tasks']],
            Step::class    => ['old' => $this->project->steps,            'new' => $newProject['steps']],
            Option::class  => ['old' => $this->project->options,          'new' => $newProject['options']],
            Price::class   => ['old' => collect([$this->project->price]), 'new' => [$newProject['price']]],
        ];
    }

    /** Проверяем необходимость логирования изменений */
    /** Может просто отправили */
    private function updateIsNeeded (): bool
    {
        $updateIsNeeded = false;

        foreach ($this->revision as $revision) {
            foreach ($revision as $value) {
                if (count($value) > 0) {
                    return true;
                }
            }
        }

        return $updateIsNeeded;
    }

    /** Создаем новую модель и записываем ей HASH-изменений */
    private function createNewProject (Revisions $revisionModel): Project
    {
        $project = Project::find($this->project['id']);
        $newProject = $project->replicate();

        $newProject->revision_id = $revisionModel->id;

        if (is_null($newProject->parent_id)) {
            $newProject->parent_id = $this->project->id;
        }

        foreach ($this->revision[Project::class]['changes'] as $change) {
            $key = $change['key'];

            $newProject->$key = $change['new_value'];

            RevisionLog::create(
                array_merge(
                    $change,
                    ['revision_id' => $revisionModel->id]
                )
            );
        }

        $newProject->save();

        return $newProject;
    }

    /** Проверяем и устанавливаем изменения в каждой модели */
    private function applyRevision (Revisions $revisionModel, string $entity, array $changes): void
    {
        if ($entity !== Project::class) {
            $this->setChanges($revisionModel, $entity);

            $this->setNew($revisionModel, $entity, $changes['new']);
        }
    }

    /** Создаем новую модель, если создали на фронте */
    private function setNew (Revisions $revisionModel, string $entity, array $newModels): void
    {
        foreach ($newModels as $newModel) {
            $values = array_merge(
                $newModel,
                ['revision_id' => $revisionModel->id],
                ['project_id' => $this->project->parent_id ?? $this->project->id]
            );

            if (array_key_exists('title', $values)) {
                $values['title'] = (strlen($values['title']) === 0) ? '' : $values['title'];
            }

            $model = $entity::create($values);

            RevisionLog::create([
                'action'            => RevisionLog::ACTION_CREATE,
                'revisionable_type' => $entity,
                'revision_id'       => $revisionModel->id,
                'model_id'          => $model->id,
                'key'               => 'id',
                'new_value'         => $model->id,
            ]);
        }
    }

    /** Применение изменений в уже существующим моделям */
    private function setChanges (Revisions $revisionModel, string $entity): void
    {
        foreach ($this->values[$entity]['old'] as $oldModelValue) {
            $oldModelId = (int) $oldModelValue['id'];

            if (in_array($oldModelId, $this->revision[$entity]['removed'])) {
                RevisionLog::create([
                    'action'            => \App\Models\RevisionLog::ACTION_DELETE,
                    'revisionable_type' => $entity,
                    'revision_id'       => $revisionModel->id,
                    'model_id'          => $oldModelId,
                    'key'               => 'id',
                ]);

                continue;
            }

            $model = $entity::find($oldModelId);

            $newModel = $model->replicate();

            $newModel->parent_id   = $oldModelValue['parent_id'] ?? $oldModelValue['id'];
            $newModel->revision_id = $revisionModel->id;

            foreach ($this->revision[$entity]['changes'] as $change) {
                if ((int) $change['model_id'] === $oldModelId) {
                    $key = $change['key'];

                    $newModel->$key = $change['new_value'];

                    RevisionLog::create(
                        array_merge(
                            $change,
                            ['revision_id' => $revisionModel->id]
                        )
                    );
                }
            }

            $newModel->save();
        }
    }
}
