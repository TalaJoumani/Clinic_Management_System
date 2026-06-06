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
}
