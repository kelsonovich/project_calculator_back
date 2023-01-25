<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
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

    Route::get('/company/inner', [CompanyController::class, 'getInnerCompanies']);
    Route::get('/company/client', [CompanyController::class, 'getClients']);

    Route::get('/company/{company}', [CompanyController::class, 'show']);
    Route::post('/company', [CompanyController::class, 'store']);
    Route::delete('/company/{company}', [CompanyController::class, 'destroy']);
    Route::patch('/company/{company}', [CompanyController::class, 'update']);

});

Route::get('/test', function () {

});



