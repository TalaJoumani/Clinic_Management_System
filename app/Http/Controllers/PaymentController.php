<?php

namespace App\Http\Controllers;

use App\Services\PaymentServices;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentServices $paymentServices;
    public function __construct(PaymentServices $paymentServices)
    {
        $this->paymentServices = $paymentServices;
    }

    public function paymentSuccess(Request $request) {
        $appointmentId=$request->query('appointment_id');
        if(!$appointmentId) {
            return response()->json([
                'message'=>'Appointment ID is required'
            ],400);
        }
      
        $result= $this->paymentServices->paymentSuccess($appointmentId);
        return response()->json([
            'message'=>'Payment successful',
            'data'=>$result,
        ]);
    }

    public function paymentCancel(Request $request) {
        $appointmentId=$request->query('appointment_id');
        if($appointmentId) {
            return $this->paymentServices->paymentCancel($appointmentId);
        }
       
        return response()->json([
            'message'=>'Payment cancelled from patient ',
        ]);
}
}
