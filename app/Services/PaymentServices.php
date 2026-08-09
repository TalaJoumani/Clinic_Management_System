<?php
namespace App\Services;

use App\Models\Appointment;
use App\Models\Medical_records;
use App\Models\Payment;
use Kreait\Firebase\Messaging\CloudMessage;

class PaymentServices {
  public function paymentSuccess($appointmentId) {
        $appointment = Appointment::findOrFail($appointmentId);
        $payment = Payment::where('appointment_id', $appointmentId)->firstOrFail();
        if ($payment->status !== 'unpaid') {
            return response()->json([
                'status' => 'error',
                'message' => 'some payment has already been made for this appointment. Please check the payment status.'
            ], 400); 
        }
        $payment->update([
            'amount_paid' => $payment->total_amount * 0.50,
            'remaining_amount' => $payment->total_amount * 0.50,
            'status' => 'partially_paid',
        ]);

        $appointment->update([
            'status' => 'confirmed',  
        ]);

        return [
            'message' => 'First deposit (50%) paid successfully. Appointment confirmed.',
            'appointment' => $appointment,
            'payment' => $payment,
        ];
    }

  public function paymentCancel(int $appointmentId) {
    $appointment = Appointment::findOrFail($appointmentId);
    $payment = Payment::where('appointment_id', $appointmentId)->firstOrFail();
    if ($appointment->status === 'cancelled') {
        return response()->json([
            'status' => 'error',
            'message' => 'this appointment is already cancelled. You cannot cancel it again.'
        ], 400);
    }
    if ($payment->status === 'fully_paid' || $appointment->status === 'completed') {
        return response()->json([
            'status' => 'error',
            'message' => 'sorry, this appointment is already completed and fully paid. You cannot cancel it now.'
        ], 400);
    }
    $appointment->update([
        'status' => 'cancelled',  
    ]);
    $payment->update([
        'amount_paid' => $payment->amount_paid, 
        'remaining_amount' => $payment->remaining_amount,
        'status' => 'cancelled', 
    ]);

    return [
        'status' => 'success',
        'message' => 'Appointment cancelled successfully. Paid deposits are non-refundable.',
        'appointment' => $appointment,
        'payment' => $payment,
    ];
}

  public function completeFinalPayment(int $appointmentId) {
        $appointment = Appointment::findOrFail($appointmentId);
        $payment = Payment::where('appointment_id', $appointmentId)->firstOrFail();
        if ($payment->status === 'fully_paid' || $appointment->status === 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Sorry, this appointment is already completed and fully paid. You cannot make another payment.'
            ], 400);
        }
        if ($payment->status !== 'partially_paid') {
            return response()->json([
                'status' => 'error',
                'message' => 'cannot complete final payment because the initial 50% deposit has not been paid yet. Please make the first payment before completing the final payment.'
            ], 400);
        }
        $payment->update([
            'amount_paid' => $payment->total_amount, 
            'remaining_amount' => 0,                
            'status' => 'fully_paid',                
        ]);

        $appointment->update([
            'status' => 'completed',                 
        ]);
            Medical_records::create([
                'patient_id'=>$appointment->patient_id,
                'doctor_id'=>$appointment->doctor_id,
                'appointment_id'=>$appointmentId
            ]);
        
       // إرسال إشعار عبر الفايربيز للطبيب
        try {
            // جلب علاقة الطبيب مع جدول المستخدمين للحصول على fcm_token
            $appointment->load('doctor.user'); 
            
            if ($appointment->doctor && $appointment->doctor->user && $appointment->doctor->user->fcm_token) {
                $token = $appointment->doctor->user->fcm_token;
                
                // رسالة الإشعار
                $messaging = app('firebase.messaging');
                $doctorName = $appointment->doctor->user->first_name . ' ' . $appointment->doctor->user->last_name;
                
                $message = CloudMessage::fromArray([
                    'token' => $token,
                    'notification' => [
                        'title' => 'new appointment🔔',
                        'body' => 'payment completed and create medical record for patient',
                    ],
                    'data' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'notification_type' => 'payment_completed',
                        'appointment_id' => (string) $appointment->id,
                        'patient_id' => (string) $appointment->patient_id,
                    ],
                  'android' => [
                        'notification' => [
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'importance' => 'HIGH',
                        ],
                    ],
                ]);

                $messaging->send($message);
                $firebase='notification sent success:'.$token;}
                else{
                    $firebase='failed';
                }
                //\Illuminate\Support\Facades\Log::info('Firebase payment notification sent for Appointment ID: ' . $appointment->id);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Firebase notification failed: ' . $e->getMessage());
        }
        
         return [
            'message' => 'Final payment received. Remaining balance cleared and appointment completed.',
            'fire'=>$firebase,
            'appointment' => $appointment,
            'payment' => $payment,
        ];
    }
}