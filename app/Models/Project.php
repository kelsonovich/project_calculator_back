<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Silber\Bouncer\Database\HasRolesAndAbilities;

class Project extends Model
{
    use HasRolesAndAbilities, HasApiTokens, HasFactory, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'start',
        'end',
        'hours_per_week',
        'client_buffer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    //2022-11-22T20:19:56.000000Z
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function price(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Price::class, 'revision_id', 'revision_id');
    }

    public function steps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Step::class, 'revision_id', 'revision_id');
    }

    public function tasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class, 'revision_id', 'revision_id');
    }

    public function options(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Option::class, 'revision_id', 'revision_id');
    }

    public static function boot()
    {
        parent::boot();

        static::deleted(function ($product) {
            $product->price()->delete();
            $product->steps()->delete();
            $product->tasks()->delete();
            $product->options()->delete();
        });
    }

    public static function getByCondition (string $id, string $revisionId): Project|null
    {
        try {
            return Project::where('revision_id', $revisionId)
                ->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($id) {
                    $query->where('id', $id)->orWhere('parent_id', $id);
                })->first();
        } catch (\Exception $exception) {
            return null;
        }
    }

    public static function getAll()
    {
        $projects = Project::where('parent_id', null)->orderBy('id', 'desc')->get();

        $newProjects = [];

        foreach ($projects as $project) {
            $newProject = Project::where('parent_id', $project->id)->orderBy('id', 'desc')->first();

            if ($newProject) {
                $newProjects[] = $newProject;
            } else {
                $newProjects[] = $project;
            }
        }

        return $newProjects;
    }

    public static function deleteAll(string $projectId): void
    {
        $projects = Project::where('id', $projectId)->orWhere('parent_id', $projectId);

        foreach ($projects->get() as $project) {
            $project->delete();
        }
    }
}
