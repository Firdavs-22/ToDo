<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ToDoController;



//Protected routes
Route::group(['middleware' => 'auth:sanctum'], function () {
    //User
    Route::post('/logout', [UserController::class, 'logout']);
    Route::put('/user', [UserController::class, 'update']);
    Route::delete('/user', [UserController::class, 'destroy']);
    Route::get('/user', [UserController::class, 'show']);

    // Categories
    Route::resource('categories', CategoryController::class);

    // ToDoList

    Route::get('/date/{priority?}', [ToDoController::class, 'taskByDate']);
    Route::get('/weekly/{priority?}', [ToDoController::class, 'weeklyTasks']);
    Route::get('/today/{priority?}', [ToDoController::class, 'todayTasks']);
    Route::get('/favorite/{priority?}', [ToDoController::class, 'favorite']);
    Route::get('/todo-category/{category}/{priority?}', [ToDoController::class, 'showCategory']);
    Route::get('/complete/{todo}', [ToDoController::class, 'complete']);
    Route::get('/step-complete/{step}', [ToDoController::class, 'stepComplete']);
    Route::get('/todo/{todo}', [ToDoController::class, 'show']);

    Route::post('/todo', [ToDoController::class, 'store']);
    Route::post('/todo/{todo}', [ToDoController::class, 'storeStep']);
    Route::put('/todo/{todo}', [ToDoController::class, 'update']);
    Route::put('/todo-step/{step}', [ToDoController::class, 'updateStep']);
    Route::put('/todo-category/{todo}', [ToDoController::class, 'updateCategory']);
    Route::delete('/todo/{todo}', [ToDoController::class, 'destroy']);
    Route::delete('/todo-step/{step}', [ToDoController::class, 'destroyStep']);

});

//Public routes
Route::group([''], function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/login', [UserController::class, 'login']);
});
