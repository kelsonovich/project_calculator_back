<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

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
        return $this->hasOne(Price::class);
    }

    public function steps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Step::class);
    }

    public function tasks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function options(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Option::class);
    }

    public static function boot ()
    {
        parent::boot();

        static::deleted(function($product)
        {
            $product->price()->delete();
            $product->steps()->delete();
            $product->tasks()->delete();
            $product->options()->delete();
        });
    }
}
