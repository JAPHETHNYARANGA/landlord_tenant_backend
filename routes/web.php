<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/password/reset/{token}', [AuthController::class, 'createPassword'])->name('password.create');

Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.reset');

