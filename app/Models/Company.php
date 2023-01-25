<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Silber\Bouncer\Database\HasRolesAndAbilities;

class Company extends Model
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
    ];

    protected $hidden = [
        'isClient',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public static function getInnerCompanies()
    {
        return Company::where('isClient', false)->get();
    }

    public static function getClients()
    {
        return Company::where('isClient', true)->get();
    }
}
