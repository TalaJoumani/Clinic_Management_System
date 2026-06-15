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
  public function getDoctorMonthlyCalendar($doctorId, $date) 
{  
    $dayName = Carbon::parse($date)->format('l');
    $doctor = Doctor::with(['schedules' => function($query) use ($dayName) {
        $query->whereIn('day', [$dayName, 'All']);
    }])->findOrFail($doctorId);

    $daySchedules = $doctor->schedules->first();
    
    // تعريف الدوام إذا وجد، وإلا نعتبر الدوام غير موجود
    $workStart = $daySchedules ? Carbon::parse($date . ' ' . $daySchedules->start_time) : null;
    $workEnd = $daySchedules ? Carbon::parse($date . ' ' . $daySchedules->end_time) : null;

    $bookedAppointments = Appointment::where('doctor_id', $doctorId)
        ->whereDate('appointment_time', $date)
        ->whereIn('status', ['confirmed', 'pending_deposit'])
        ->pluck('status', 'appointment_time')
        ->toArray();

    $slots = [];
    $dayStart = Carbon::parse($date . ' 09:00:00');
    $dayEnd = Carbon::parse($date . ' 22:00:00');
    
    while ($dayStart->lt($dayEnd)) {
        // التحقق هل الموعد داخل وقت الدوام
        $isInside = ($workStart && $workEnd) ? $dayStart->between($workStart, $workEnd) : false;
        
        $slotString = $dayStart->format('Y-m-d H:i:s');
        $isBooked = array_key_exists($slotString, $bookedAppointments);
        $displayType='clinic_offline';
        if($isBooked){
            $displayType='booked';
        }else if($isInside){
            $displayType='available';
        }
        $slots[] = [
            'time' => $dayStart->format('H:i'),
            'full_date' => $slotString,
            'is_booked' => $isBooked,
            'status' => $isBooked ? $bookedAppointments[$slotString] : 'available',
            'is_clinic_hour' => $isInside ,
            'display_type'=>$displayType,
        ];
        
        $dayStart->addMinutes(30); 
    }

    return $slots;
}
public function addBooking($patientId, array $data) {
    $user = auth('sanctum')->user();
    $appointmentTimeParsed = Carbon::parse($data['appointment_time']);
    $timeOnly = $appointmentTimeParsed->format('H:i:s');
    $dayName = $appointmentTimeParsed->format('l');

    // 0. التحقق من أن الموعد ضمن ساعات عمل العيادة (08:00 - 22:00)
    if ($timeOnly < '08:00:00' || $timeOnly > '22:00:00') {
        return response()->json(['message' => 'Appointments can only be booked between 08:00 and 22:00'], 422);
    }

    // 1. التحقق من الحجز المسبق
    $isBooking = Appointment::where('doctor_id', $data['doctor_id'])
        ->where('appointment_time', $data['appointment_time'])
        ->whereIn('status', ['confirmed', 'pending_deposit'])
        ->exists();
    if ($isBooking) {
        return response()->json(['message' => 'this slot is already booked'], 400);
    }

    $doctor = Doctor::findOrFail($data['doctor_id']);
    
    // 2. التحقق من دوام الدكتور (مع دعم 'All' في جدول الـ schedules)
    $isInsideWorkingHours = DB::table('doctor_schedules')
        ->where('doctor_id', $data['doctor_id'])
        ->where(function($query) use ($dayName) {
            $query->where('day', $dayName)
                  ->orWhere('day', 'All');
        })
        ->whereTime('start_time', '<=', $timeOnly)
        ->whereTime('end_time', '>=', $timeOnly)
        ->exists();

    // 3. قواعد الحجز
    if ($data['type'] === 'clinic') {
        if (!$isInsideWorkingHours) {
            return response()->json(['message' => 'Clinic appointments must be booked within doctor working hours.'], 422);
        }
    } else {
        // حجز أونلاين أو منزلي: يجب أن يكون خارج دوام الدكتور
        if ($isInsideWorkingHours) {
            return response()->json(['message' => 'Online/Home visits cannot be booked during clinic working hours.'], 422);
        }
        
        if ($data['type'] === 'home') {
            if (!$doctor->home_visit) {
                return response()->json(['message' => 'This doctor does not offer home visits'], 400);
            }
            if (!Location::where('id', $data['location_id'])->where('user_id', $patientId)->exists()) {
                return response()->json(['message' => 'Invalid location ID'], 400);
            }
        }
    }

    // 4. حساب السعر
    $doctorSchedule = DB::table('doctor_schedules')
        ->where('doctor_id', $data['doctor_id'])
        ->where(function($query) use ($dayName) {
            $query->where('day', $dayName)->orWhere('day', 'All');
        })
        ->first();
        
    $basePrice = ($doctorSchedule && $doctorSchedule->price) ? $doctorSchedule->price : 100;
    
    $totalPrice = $basePrice;
    if ($data['type'] === 'online') {
        $totalPrice = $basePrice * 0.80;
    } elseif ($data['type'] === 'home') {
        $totalPrice = $basePrice * 2;
    }

    $depositAmount = $totalPrice * 0.50;
    $remainingAmount = $totalPrice - $depositAmount;

    // 5. الحجز (Transaction)
    return DB::transaction(function () use ($patientId, $data, $totalPrice, $depositAmount, $remainingAmount, $user) {
        $appointment = Appointment::create([
            'patient_id' => $patientId,
            'doctor_id' => $data['doctor_id'],
            'type' => $data['type'],
            'appointment_time' => $data['appointment_time'],
            'location_id' => $data['location_id'] ?? null,
            'status' => 'pending_deposit',
        ]);

        Payment::create([
            'appointment_id' => $appointment->id,
            'total_amount' => $totalPrice,
            'amount_paid' => 0,
            'remaining_amount' => $remainingAmount,
            'method' => 'electronic',
            'status' => 'unpaid',
        ]);

        $paymentUrl = $this->generateFatoraLink($appointment->id, $depositAmount, $user);
        
        return [
            'appointment' => $appointment,
            'payment_url' => $paymentUrl,
            'amount_to_pay_now' => $depositAmount,
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

public function confirm($appointmentId) {
    $appointment=Appointment::with('patient')->findOrFail($appointmentId);
   $appointment->update([
    'status'=>'confirmed',
   ]);
   $paymentUrl=$this->generateFatoraLink($appointment->id,$appointment->payment->remaining_amount,$appointment->patient);
    return [
     'message'=>'Appointment confirmed, please proceed to payment',
     'payment_url'=>$paymentUrl,
    ];
}


public function cancel($appointmentId) {
    $appointment=Appointment::findOrFail($appointmentId);
   $appointment->update([
    'status'=>'cancelled',
   ]);
  if($appointment->payment){
    $appointment->payment->update([
        'status'=>'unpaid',
    ]);
  }
    return [
     'message'=>'Appointment cancelled and payment updated',
    ]  ;
}

}
