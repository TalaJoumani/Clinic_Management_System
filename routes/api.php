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
use App\Http\Controllers\NotificationController;

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
Route::post('logout', [AuthController::class, 'logout']);
Route::get('getMyProfile', [userController::class, 'getMyProfile']);

    Route::post('addAdmin', [SuperAdminController::class, 'addAdmin']);
    Route::delete('deleteAdmin', [SuperAdminController::class, 'deleteAdmin']);
    Route::get('getAllAdmins', [SuperAdminController::class, 'getAllAdmins']);
    Route::get('getUsersByRole', [SuperAdminController::class, 'getUsersByRole']);
    Route::post('createItem', [SuperAdminController::class, 'createItem']);
    Route::post('addItem/{id}',[SuperAdminController::class,'addItem']);
    Route::get('filter',[SuperAdminController::class,'filter']);
    Route::get('getMonthlyFinancialReport',[SuperAdminController::class,'getMonthlyFinancialReport']);
    Route::get('getPeakTimesAndTopDoctor',[SuperAdminController::class,'getPeakTimesAndTopDoctor']);

    Route::post('addDoctor', [AdminController::class, 'addDoctor']);
    Route::delete('deleteDoctor', [AdminController::class, 'deleteDoctor']);
    Route::get('getAllDoctors', [AdminController::class, 'getAllDoctors']);
    Route::put('updateDoctor', [AdminController::class, 'updateDoctor']);
    Route::get('getItems',[AdminController::class,'getItems']);
    Route::post('useItem/{id}',[AdminController::class,'useItem']);



    Route::get('getDoctorMonthlyCalendar', [AppointmentController::class, 'getDoctorMonthlyCalendar']);
    Route::post('addBooking', [AppointmentController::class, 'addBooking']);
    Route::post('appointments/{id}/app-confirm', [AppointmentController::class, 'appConfirm']);
    Route::post('appointments/{id}/app-cancel', [AppointmentController::class, 'appCancel']);
    
    
    Route::get('paymentSuccess', [PaymentController::class, 'paymentSuccess']);
    Route::get('paymentCancel', [PaymentController::class, 'paymentCancel']);
    Route::get('completeFinalPayment', [PaymentController::class, 'completeFinalPayment']);


    Route::post('addLocation', [LocationController::class, 'addLocation']);


    Route::get('appointmentForDoctor',[DoctorController::class,'getAppointmentForDoctor']);
    Route::get('getMedicalRecord/{patientId}',[DoctorController::class,'getMedicalRecord']);
    Route::post('updateMedicalRecord',[DoctorController::class,'updateMedicalRecord']);
    Route::get('getPatientLocation/{appointmentId}',[DoctorController::class,'getPatientLocation']);
    


    Route::get('getAllDoctorsForPatient',[PatientController::class,'getAllDoctors']);
    Route::get('getSpcialization',[PatientController::class,'getSpcialization']);
    Route::get('filterDoctor',[PatientController::class,'filterDoctor']);
    Route::get('getMedicaleRecord',[PatientController::class,'getMedicaleRecord']);
    Route::get('exportMedicalRecords',[PatientController::class,'exportMedicalRecords']);
    Route::get('getAppointmentPatient',[PatientController::class,'getAppointmentPatient']);
    Route::get('getActiveOffers',[PatientController::class,'getActiveOffers']);

     Route::post('updateFcmToken', [NotificationController::class, 'updateToken']);
    Route::post('generalTestNotification', [NotificationController::class, 'generalTestNotification']);
});
