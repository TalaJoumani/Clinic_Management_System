<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddBookingRequest;
use App\Http\Requests\GetCalendarRequest;
use App\Services\AppointmentServices;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    protected AppointmentServices $appointmentServices;
    public function __construct(AppointmentServices $appointmentServices)
    {
        $this->appointmentServices = $appointmentServices;
    }

    public function getDoctorMonthlyCalendar(GetCalendarRequest $getCalendarRequest)
    {
       $slots=$this->appointmentServices->getDoctorMonthlyCalendar($getCalendarRequest->doctor_id,$getCalendarRequest->date);
       return response()->json([
        'slots'=>$slots
        ]);
    }

    public function addBooking(AddBookingRequest $addBookingRequest) {
        $response=$this->appointmentServices->addBooking(auth('sanctum')->id(),$addBookingRequest->validated());
        return $response;
}

   public function appConfirm($id) {
    $response = $this->appointmentServices->confirm($id);
        return response()->json([
        'status' => 'success',
        'message' => 'Appointment status updated to confirmed, please complete payment.',
        'payment_url' => $response['payment_url'] ?? null
    ], 200);
}

public function appCancel($id) {
    $response = $this->appointmentServices->cancel($id);
    
    return response()->json([
        'status' => 'success',
        'message' => 'Appointment cancelled and billing updated successfully.'
    ], 200);
}


public function updateFcmToken(Request $request) {
    $request->validate([
        'fcm_token' => 'required|string'
    ]);
    /** @var \App\Models\User|null $user */
    $user = auth('sanctum')->user();
    
    if ($user) {
        $user->update([
            'fcm_token' => $request->fcm_token
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'FCM Token updated successfully.'
        ], 200);
    }

    return response()->json([
        'status' => 'error',
        'message' => 'User not authenticated'
    ], 401);
}
}
