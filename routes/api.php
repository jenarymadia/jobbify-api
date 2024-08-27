<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



// Route::post('/tokens/create', function (Request $request) {
//     $token = $request->user()->createToken($request->token_name);
 
//     return ['token' => $token->plainTextToken];
// });


Route::post('/auth/register', [AuthController::class, 'createUser']);
Route::post('/auth/login', [AuthController::class, 'loginUser']);
