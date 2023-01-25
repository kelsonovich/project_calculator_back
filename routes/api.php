<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::get('/project/{projectId}/{revisionId}', [ProjectController::class, 'show']);
    Route::delete('/project/{projectId}/{revisionId}', [ProjectController::class, 'destroy']);

    Route::apiResource('/project', ProjectController::class);
    Route::post('/project/calculate', [ProjectController::class, 'calculate']);
});

Route::get('/test', function () {
    $projectId = '984d29b4-590c-4d76-aac2-1d941a42ccdc';
    $projects = \App\Models\Project::where('id', $projectId)->orWhere('parent_id', $projectId);

    $test = [];
    foreach ($projects->get() as $project) {
        $test[] = $project->title;
    }

    dd($test);
});
