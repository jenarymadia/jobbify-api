<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;



Route::post('/auth/register', [AuthController::class, 'createUser']);
Route::post('/auth/login', [AuthController::class, 'loginUser']);

Route::get('/user', function (Request $request) {
    return Auth::user();
})->middleware('auth:sanctum');

Route::prefix('clients')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ClientController::class, 'index']); // List clients
    Route::post('/', [ClientController::class, 'store']); // Create a new client
    Route::put('/{client}', [ClientController::class, 'update']);
    Route::get('/statuses', [ClientController::class, 'statuses']); // Get statuses for leads
    Route::delete('/{client}', [ClientController::class, 'destroy']);
});