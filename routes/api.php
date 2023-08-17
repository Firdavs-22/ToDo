<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ToDoController;

//Protected routes
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/test', [ToDoController::class, 'test']);
    Route::post('/logout', [UserController::class, 'logout']);
    Route::resource('categories', CategoryController::class);
});

//Public routes
Route::group([''], function () {
    Route::post('/register', [UserController::class, 'register']);
    Route::post('/login', [UserController::class, 'login']);
});
