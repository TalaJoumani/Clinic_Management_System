<?php
namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Location;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentServices {
    public function getDoctorMonthlyCalendar($doctorId,$date) {  
        $dayName = Carbon::parse($date)->format('l');
        $doctor=Doctor::with(['schedules' => function($query) use ($dayName) {
            $query->whereiN('day', [$dayName,'All']);
        }])->findOrFail($doctorId);
        $daySchedules = $doctor->schedules->first();
        if (!$daySchedules || !$daySchedules->start_time || !$daySchedules->end_time) {
            return [];
        }
        $bookedAppointment=Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_time', $date)
            ->whereIn('status', ['confirmed', 'pending'])
            ->pluck('status', 'appointment_time')
            ->toArray();

            $slots=[];
            $startTime=Carbon::parse($date.' '.$daySchedules->start_time);
            $endTime=Carbon::parse($date.' '.$daySchedules->end_time);
            while($startTime->lt($endTime)){
                $slotString=$startTime->format('Y-m-d H:i:s');
                $isBooked=array_key_exists($slotString, $bookedAppointment);
                $slots[]=[
                    'time'=>$startTime->format('H:i'),
                    'full_date'=>$slotString,
                    'is_booked'=>$isBooked,
                    'status'=>$isBooked ? $bookedAppointment[$slotString] : 'available',
                ];
                $startTime->addMinutes(30); 
            }

        return $slots;

}


public function addBooking($patientId,array $data) {
    $user=auth('sanctum')->user();
    $isBooking=Appointment::where('doctor_id', $data['doctor_id'])
        ->where('appointment_time', $data['appointment_time'])
        ->whereIn('status', ['confirmed', 'pending_deposit'])
        ->exists();
    if($isBooking){
        return response()->json([
            'message'=>'this slot is already booked',
        ]);
    }
    $doctor=Doctor::findOrFail($data['doctor_id']);
    $doctorSchedule=DB::table('doctor_schedules')
        ->where('doctor_id', $data['doctor_id'])
        ->first();
    $price=($doctorSchedule && $doctorSchedule->price) ? $doctorSchedule->price : 100; 
    $totalPrice=$price;
    if (isset($data['type']) && $data['type'] === 'online') {
        $totalPrice = $price * 0.80;
    } elseif (isset($data['type']) && $data['type'] === 'home') {
        if (!$doctor->home_visit) {
            return response()->json([
                'message' => 'This doctor does not offer home visits',
            ], 400);
        }
        $locationExists=Location::where('id', $data['location_id'])
            ->where('user_id', $patientId)
            ->exists();
        if (!$locationExists) {
            return response()->json([
                'message' => 'Invalid location ID',
            ], 400);
        }
        $isDuringWorkingHours = DB::table('doctor_schedules')
            ->where('doctor_id', $data['doctor_id'])
            ->where(function($query) use ($data) {
                $query->where('start_time', '<=', date('H:i', strtotime($data['appointment_time'])))
                    ->where('end_time', '>=', date('H:i', strtotime($data['appointment_time'])));
            })->exists();

        if ($isDuringWorkingHours) {
            return response()->json([
                'message' => 'Home visits are not allowed during doctor working hours , must be scheduled outside of clinic working',
            ], 422);
        }

        $totalPrice = $price * 2;
    } else {
        $totalPrice = $price;
    }


    $depositAmount=$totalPrice*0.50;
    $remainingAmount=$totalPrice-$depositAmount;
            return DB::transaction(function() use ($patientId, $data, $totalPrice, $depositAmount, $remainingAmount, $user) {

    $appointment=Appointment::create([
        'patient_id'=>$patientId,
        'doctor_id'=>$data['doctor_id'],
        'type'=>$data['type'],
        'appointment_time'=>$data['appointment_time'],
        'location_id'=>$data['location_id'] ?? null,
        'status'=>'pending_deposit',
    ]);

    $payment=Payment::create([
        'appointment_id'=>$appointment->id,
        'total_amount'=>$totalPrice,
        'amount_paid'=>0,
        'remaining_amount'=>$remainingAmount,
        'method'=>'electronic',
        'status'=>'unpaid',
    ]);

    $paymentUrl=$this->generateFatoraLink($appointment->id,$depositAmount,$user);
    return [
        'appointment'=>$appointment,
        'payment_details'=>$payment,
        'payment_url'=>$paymentUrl,
        'amount_to_pay_now'=>$depositAmount,
    ];
});
}

    /**
     * Generate a payment link for Fatora (placeholder implementation).
     * Replace with actual integration logic as needed.
     *
     * @param int $appointmentId
     * @param float $amount
     * @param array $data
     * @return string
     */
protected function generateFatoraLink($appointmentId, $amountToPay, $user)
{
    $baseUrl = config('services.fatora.base_url');

    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'api_key' => (string) env('FATORA_API_KEY', ''),
        'Content-Type' => 'application/json',
        'Accept'       => 'application/json',
    ])->post($baseUrl, [
        'amount'      => (float) $amountToPay,
        'currency'    => 'SAR', 
        'order_id'    => (string) $appointmentId,
        'client'      => [
            'name'  => $user->first_name,
            'phone' => $user->phone,
            'email' => $user->email,
        ],
        'language'    => 'en',
        'success_url' => 'http://domain.com/payments/success',
        'failure_url' => 'http://domain.com/payments/failure',
        'fcm_token'   => 'XXXXXXXXX',
        'save_token'  => true,
        'note'        => 'some additional info'
    ]);

    if ($response->successful()) {
        return $response->json('result.checkout_url');
    }

    Log::error('Fatora API Error: ' . $response->body());
    return null;
}
}
