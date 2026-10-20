<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/home');

Route::view('/login', 'login')->middleware('guest')->name('login');
Route::post('/login', [LoginController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])->name('login.store');
Route::view('/home', 'home')->middleware('auth')->name('home');
Route::get('/book/list', [BookingController::class, 'index'])->middleware('auth')->name('bookings.index');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
