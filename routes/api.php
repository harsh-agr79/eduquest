<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FPSController;

Route::post('/login', [AuthController::class, 'login']); //
Route::post('/register', [AuthController::class, 'register']); //

Route::get('/fps', [FPSController::class, 'index']); //
Route::get('/fps/{id}', [FPSController::class, 'details']); //

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
