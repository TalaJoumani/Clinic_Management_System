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

    public function confirm($id) {
        $response=$this->appointmentServices->confirm($id);
        $paymentUrl=$response['payment_url'] ?? null;
        return "<div style='text-align: center; font-family: sans-serif; padding: 50px; background-color: #f9f9f9;'>
            <h1 style='color: #001f3f;'>Appointment Confirmed Successfully!</h1>
            <p>Your booking is now confirmed. Please proceed to payment to finalize your visit.</p>
            
            <a href='$paymentUrl' style='background-color: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 18px; font-weight: bold; display: inline-block; margin-top: 20px;'>
                Pay Now
            </a>
        </div>
    ";
    }

    public function cancel($id) {
        $response=$this->appointmentServices->cancel($id);
        return $response;
    }
}
