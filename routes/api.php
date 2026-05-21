<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('register', [AuthController::class, 'register']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('login', [AuthController::class, 'login']);



Route::middleware('auth:sanctum')->group(function () {
    Route::post('addAdmin', [SuperAdminController::class, 'addAdmin']);
    Route::delete('deleteAdmin', [SuperAdminController::class, 'deleteAdmin']);
    Route::get('getAllAdmins', [SuperAdminController::class, 'getAllAdmins']);

    Route::post('addDoctor', [AdminController::class, 'addDoctor']);
    Route::delete('deleteDoctor', [AdminController::class, 'deleteDoctor']);
    Route::get('getAllDoctors', [AdminController::class, 'getAllDoctors']);
    Route::put('updateDoctor', [AdminController::class, 'updateDoctor']);

    

});
