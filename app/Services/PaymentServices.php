<?php
namespace App\Services;

use App\Models\Appointment;
use App\Models\Payment;

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

  public function paymentCancel($appointmentId) {
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

  public function completeFinalPayment($appointmentId) {
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
         return [
            'message' => 'Final payment received. Remaining balance cleared and appointment completed.',
            'appointment' => $appointment,
            'payment' => $payment,
        ];
    }
}