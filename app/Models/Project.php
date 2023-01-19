<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Silber\Bouncer\Database\HasRolesAndAbilities;

class Project extends Model
{
    use HasRolesAndAbilities, HasApiTokens, HasFactory, SoftDeletes;

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

    public function getByCondition (int $id, $revisionId = null): Project
    {
        return ((is_null($revisionId)))
            ? Project::find($id)->with('steps', 'tasks', 'options', 'price')->first()
            : Project::where('parent_id', $id)
                ->where('revision_id', $revisionId)
                ->with('price', 'steps', 'tasks', 'options')
                ->first();
    }
}
