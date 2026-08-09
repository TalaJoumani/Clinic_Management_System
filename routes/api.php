<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\userController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('register', [AuthController::class, 'register']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgotPassword', [AuthController::class, 'forgotPassword']);
Route::post('verifyResetOtp', [AuthController::class, 'verifyResetOtp']);
Route::post('resetPassword', [AuthController::class, 'resetPassword']);


Route::middleware('auth:sanctum')->group(function () {

Route::get('getMyProfile', [userController::class, 'getMyProfile']);

    Route::post('addAdmin', [SuperAdminController::class, 'addAdmin']);
    Route::delete('deleteAdmin', [SuperAdminController::class, 'deleteAdmin']);
    Route::get('getAllAdmins', [SuperAdminController::class, 'getAllAdmins']);
    Route::get('getUsersByRole', [SuperAdminController::class, 'getUsersByRole']);

    Route::post('addDoctor', [AdminController::class, 'addDoctor']);
    Route::delete('deleteDoctor', [AdminController::class, 'deleteDoctor']);
    Route::get('getAllDoctors', [AdminController::class, 'getAllDoctors']);
    Route::put('updateDoctor', [AdminController::class, 'updateDoctor']);
    Route::post('addOffer',[AdminController::class,'createOffer']);
     //
    Route::get('getDoctorMonthlyCalendar', [AppointmentController::class, 'getDoctorMonthlyCalendar']);
    Route::post('addBooking', [AppointmentController::class, 'addBooking']);
    Route::post('appointments/{id}/app-confirm', [AppointmentController::class, 'appConfirm']);
    Route::post('appointments/{id}/app-cancel', [AppointmentController::class, 'appCancel']);
    Route::post('updateFcmToken', [AppointmentController::class, 'updateFcmToken']);
    
    
    Route::get('paymentSuccess', [PaymentController::class, 'paymentSuccess']);
    Route::get('paymentCancel', [PaymentController::class, 'paymentCancel']);
    Route::get('completeFinalPayment', [PaymentController::class, 'completeFinalPayment']);

    Route::post('addLocation', [LocationController::class, 'addLocation']);

    Route::get('appointmentForDoctor',[DoctorController::class,'getAppointmentForDoctor']);
    Route::get('getMedicalRecord/{patientId}',[DoctorController::class,'getMedicalRecord']);
    Route::post('updateMedicalRecord',[DoctorController::class,'updateMedicalRecord']);
    
    Route::get('getAllDoctorsForPatient',[PatientController::class,'getAllDoctors']);
    Route::get('getSpcialization',[PatientController::class,'getSpcialization']);
    Route::get('filterDoctor',[PatientController::class,'filterDoctor']);
    Route::get('getMedicaleRecord',[PatientController::class,'getMedicaleRecord']);
    Route::get('exportMedicalRecords',[PatientController::class,'exportMedicalRecords']);
    Route::get('getAppointmentPatient',[PatientController::class,'getAppointmentPatient']);
    Route::get('getActiveOffers',[PatientController::class,'getActiveOffers']);
});
