<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Item;
use App\Services\AdminServices;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Kreait\Firebase\Exception\AppCheckApiExceptionConverter;

class NotificationController extends Controller
{
    public function updateToken(Request $request)
    {
       $request->user()->update(['fcm_token' => $request->fcm_token]);
         return response()->json(['message' => 'Token saved successfully']);
    }
public function generalTestNotification(Request $request)
{
    $request->validate([
        'type' => 'required|string|in:reminder,cancellation,payment,low_stock',
        'id'   => 'required|integer',
    ]);

    try {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            
            // 1. إشعار التذكير بالموعد
            case 'reminder':
                $appointment = Appointment::with('doctor.user')->findOrFail($id);
                $token = $appointment->doctor->user->fcm_token ?? null;
                if ($token) {
                    $this->sendFirebaseNotification($token, $appointment);
                }
                break;

            // 2. إشعار إلغاء الموعد
            case 'cancellation':
                $appointment = Appointment::with('doctor.user')->findOrFail($id);
                $token = $appointment->doctor->user->fcm_token ?? null;
                if ($token) {
                    $this->sendCancellationNotification($token, $appointment);
                }
                break;

            // 3. إشعار الدفع (موجود ضمن PaymentServices حسب كودك)
            case 'payment':
                $paymentService = new \App\Services\PaymentServices();
                $paymentService->completeFinalPayment($id);
                break;

            // 4. إشعار نقص المخزون (موجود ضمن AdminServices)
            case 'low_stock':
                $item = \App\Models\Item::findOrFail($id);
                $adminService = app(AdminServices::class);
                $adminService->sendLowStockNotification($item);
                break;
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Test notification for type '{$type}' triggered successfully!"
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to trigger test notification.',
            'error'   => $e->getMessage()
        ], 500);
    }
}


    public function sendCancellationNotification($token, $appointment) {
        try {
            $messaging = app('firebase.messaging');
                        $doctorName = $appointment->doctor->user->first_name . ' ' . $appointment->doctor->user->last_name;

            $message = CloudMessage::fromArray([
                'token' => $token,
                'notification' => [
                    'title' => 'cancellation of your appointment',
                    'body' =>'sorry, your appointment with Dr. ' . $doctorName . ' on ' . $appointment->appointment_time->format('Y-m-d H:i') . ' has been cancelled due to non-payment.',
                ],
                'data' => [
                    'click_action'      => 'FLUTTER_NOTIFICATION_CLICK',
                    'notification_type' => 'appointment_cancelled',
                    'appointment_id'    => (string) $appointment->id,
                ],
                'android' => [
                    'notification' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'importance'   => 'HIGH'
                    ],
                ]
            ]);

            $messaging->send($message);
            Log::info('Firebase cancellation notification sent for Appointment ID: ' . $appointment->id);
        } catch (\Exception $e) {
            Log::error('Firebase cancellation failed for Appointment ID ' . $appointment->id . ': ' . $e->getMessage());
        }
    }

    
    public function sendFirebaseNotification($token, $appointment) {
        try {
            $messaging = app('firebase.messaging');
            $doctorName = $appointment->doctor->user->first_name . ' ' . $appointment->doctor->user->last_name;
            $formattedTime = Carbon::parse($appointment->appointment_time)->format('h:i A');

            $message = CloudMessage::fromArray([
                'token' => $token,
                'notification' => [
                    'title' => 'Confirm Your Attendance 🔔',
                    'body' => "You have an appointment tomorrow with Dr. {$doctorName} at {$formattedTime}. Tap to confirm and pay remaining amount.",
                ],
                'data' => [
                    'click_action'      => 'FLUTTER_NOTIFICATION_CLICK',
                    'notification_type' => 'appointment_reminder',
                    'appointment_id'    => (string) $appointment->id,
                    'action_required'   => 'confirm_and_pay'
                ],
                'android' => [
                    'notification' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'importance'   => 'HIGH'
                    ],
                ]
            ]);

            $messaging->send($message);
            Log::info('Firebase reminder sent for Appointment ID: ' . $appointment->id);
        } catch (\Exception $e) {
            Log::error('Firebase failed for Appointment ID ' . $appointment->id . ': ' . $e->getMessage());
        }
    }
}


