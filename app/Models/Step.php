<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Silber\Bouncer\Database\HasRolesAndAbilities;

class Step extends Model
{
    use HasRolesAndAbilities, HasApiTokens, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'code',
        'description',
        'project_id',
//        'showInClient',
        'employee_quantity',
        'agreement',
        'parallels',
        'sort',
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

    protected $casts = [
        'showInClient' => 'boolean',
    ];

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class);
    }
}
