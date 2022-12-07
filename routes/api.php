<?php

use App\Http\Controllers\Api\OptionController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\StepController;
use App\Http\Controllers\Api\TaskController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('project', ProjectController::class);

Route::prefix('project')->group(function () {
    Route::apiResource('/step', StepController::class);
    Route::apiResource('/task', TaskController::class);
    Route::apiResource('/option', OptionController::class);
});

//Route::prefix('project')->group(function () {
//    Route::apiResource('', ProjectController::class);
//    Route::apiResource('/step', StepController::class);
//    Route::apiResource('/task', StepController::class);
//    Route::apiResource('/option', StepController::class);
//});



