<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppointmentController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/appointment/confirm/{id}',[AppointmentController::class,'confirm']);
Route::get('/appointment/cancel/{id}',[AppointmentController::class,'cancel']);