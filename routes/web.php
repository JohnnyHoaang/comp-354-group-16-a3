<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/login', 'login')->middleware('guest')->name('login');
Route::post('/login', [LoginController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])->name('login.store');
Route::view('/home', 'home')->middleware('auth')->name('home');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
