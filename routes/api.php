<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DataController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;



Route::post('/auth/register', [AuthController::class, 'createUser']);
Route::post('/auth/login', [AuthController::class, 'loginUser']);

Route::get('/user', [UserController::class, 'index'])->middleware('auth:sanctum');

Route::prefix('clients')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ClientController::class, 'index']); // List clients
    Route::post('/', [ClientController::class, 'store']); // Create a new client
    Route::put('/{client}', [ClientController::class, 'update']);
    Route::get('/statuses', [ClientController::class, 'statuses']); // Get statuses for leads
    Route::delete('/{client}', [ClientController::class, 'destroy']);
});

Route::prefix('staffs')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [StaffController::class, 'index']); // List staffs
    Route::get('/{id}', [StaffController::class, 'show']);
    Route::post('/', [StaffController::class, 'store']); // Create a new client
    Route::put('/{user}', [StaffController::class, 'update']);
    Route::delete('/{user}', [StaffController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->get('/roles', [DataController::class, 'index']);