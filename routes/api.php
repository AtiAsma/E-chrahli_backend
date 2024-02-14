<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorkerController;
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

//Protected student routes
Route::middleware(['isStudent'])->group(function() {
    Route::controller(StudentController::class)->group(function () {
        Route::get('/student/logout', 'logout');
        Route::put('/student/{student_id}', 'update');
        Route::put('/student/{student_id}/change_password', 'changePasswordWithoutReset');
    });

    Route::controller(TaskController::class)->group(function () {
        Route::post('/tasks', 'store'); 
        Route::get('/student/{id}/tasks', 'viewHistory'); 
        Route::get('/task/{id}/microtasks', 'composeAnswer'); 
        Route::get('/student/{id}/tasks/last', 'getLastAnswer');
    });

    Route::controller(RecommendationController::class)->group(function () {
        Route::get('/recommendation', 'seeRecommendations');
    });
});

//Protected worker routes
Route::middleware(['isWorker'])->group(function() {
    Route::controller(TaskController::class)->group(function () {
        Route::post('/tasks/{id}', 'getMicrotasksFromWorker'); 
        Route::post('/microtasks/{id}/answer', 'answerMicrotask'); 
        Route::get('/worker/{id}/microtasks', 'getMyMicrotasks');
        Route::get('/worker/{id}/tasks', 'getMyTasks'); 
    });

    Route::controller(RecommendationController::class)->group(function () {
        Route::post('/recommendation', 'recommendExercicesAndTopics');
        Route::get('/worker/{id}/recommendation', 'getWorkerRecommendations');
    });

    Route::controller(WorkerController::class)->group(function () {
        Route::get('/worker/logout', 'logout');
        Route::get('/worker/{id}/availability', 'getAvailabilityStatus');
        Route::put('/worker/{id}/availability', 'toggleAvailabilityStatus');
    });
});

//Protected admin routes
Route::middleware(['isAdmin'])->group(function() {
    Route::controller(WorkerController::class)->group(function () {
        Route::post('/worker', 'addWorker');
        Route::delete('/worker/{id}', 'deleteWorker');
    });

    Route::controller(AdminController::class)->group(function () {
        Route::post('/admin', 'addAdmin');
        Route::get('/admin/logout', 'logout');
        Route::delete('/admin/{id}', 'deleteAdmin');
    });
});



Route::controller(StudentController::class)->group(function () {
    Route::post('/student/register', 'register'); 
    Route::post('/student/login', 'login'); 
    Route::get('/student/{id}', 'show');
});

Route::controller(WorkerController::class)->group(function () {
    Route::post('/worker/login', 'login'); 
});

Route::controller(AdminController::class)->group(function () {
    Route::post('/admin/login', 'login');
});
