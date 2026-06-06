<?php
namespace App\Services;

use App\Models\Appointment;
use App\Models\Payment;

class PaymentServices {
  public function paymentSuccess($appointmentId) {
   $appointment=Appointment::findOrFail($appointmentId);
   
   $payment=Payment::where('appointment_id', $appointmentId)->firstOrFail();
   $payment->update([
    'amount_paid'=>$payment->total_amount*0.50,
    'remaining_amount'=>$payment->total_amount*0.50,
    'status'=>'partially_paid',
   ]);

   $appointment->update([
    'status'=>'confirmed',  
   ]);
    return [
     'appointment'=>$appointment,
     'payment'=>$payment,
    ];
  }


  public function paymentCancel($appointmentId) {
    $appointment=Appointment::findOrFail($appointmentId);
    $appointment->update([
     'status'=>'cancelled',  
    ]);
    $payment=Payment::where('appointment_id', $appointmentId)->firstOrFail();
    $payment->update([
     'amount_paid'=>0,
     'remaining_amount'=>$payment->total_amount,
     'status'=>'unpaid',
    ]);

     return [
      'message'=>'Payment cancelled and appointment updated',
     ]  ;
}
}