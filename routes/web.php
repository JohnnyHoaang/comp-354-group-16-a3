<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingPlaygroundController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/home');

Route::view('/login', 'login')->middleware('guest')->name('login');
Route::post('/login', [LoginController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])->name('login.store');
Route::get('/home', [HomeController::class, 'index'])->middleware('auth')->name('home');
Route::get('/book/list', [BookingController::class, 'index'])->middleware('auth')->name('bookings.index');
Route::view('/book/playground', 'bookings.playground')->middleware('auth')->name('bookings.playground');
Route::post('/book/playground/add', [BookingPlaygroundController::class, 'store'])->middleware('auth')->name('bookings.playground.add');
Route::post('/book/playground/clear', [BookingPlaygroundController::class, 'destroy'])->middleware('auth')->name('bookings.playground.clear');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
