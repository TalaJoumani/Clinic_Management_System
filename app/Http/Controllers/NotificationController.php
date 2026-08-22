<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Item;
use App\Models\Notification;
use App\Models\User;
use App\Services\AdminServices;
use App\Services\SuperAdminServices;
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

    /**
     * جلب إشعارات المستخدم الحالي (للداشبورد / bell icon).
     */
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->limit(50)
            ->get();

        return response()->json($notifications);
    }

    /**
     * تحديد إشعار كمقروء.
     */
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['read_at' => now()]);
        return response()->json(['message' => 'marked as read']);
    }

    /**
     * تخزين إشعار بالداتابيس (مصدر البيانات لل bell icon بالداشبورد).
     */
    private function storeNotification($userId, $title, $body, $type, $data = [])
    {
        return Notification::create([
            'user_id' => $userId,
            'title'   => $title,
            'body'    => $body,
            'type'    => $type,
            'data'    => $data,
        ]);
    }

public function generalTestNotification(Request $request)
{
    $request->validate([
        'type' => 'required|string|in:reminder,cancellation,payment,low_stock,restock',
        'id'   => 'required|integer',
        'addedQuantity' => 'required_if:type,restock|integer|min:1',
    ]);

    try {
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            
            // 1. إشعار التذكير بالموعد
            case 'reminder':
                $appointment = Appointment::with(['doctor', 'patient'])->findOrFail($id);
                $this->sendFirebaseNotification($appointment->patient->fcm_token ?? null, $appointment);
                break;

            // 2. إشعار إلغاء الموعد
            case 'cancellation':
                $appointment = Appointment::with(['doctor', 'patient'])->findOrFail($id);
                $this->sendCancellationNotification($appointment->patient->fcm_token ?? null, $appointment);
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

                case 'restock':
                $item = \App\Models\Item::findOrFail($id);
                $superAdminService = app(SuperAdminServices::class);
                $addedQuantity = $request->integer('addedQuantity');
                $superAdminService->sendAddNotification($item, $addedQuantity);
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
            // doctor_id stores users.id (Flutter / booking contract)
            $doctorUser = User::find($appointment->doctor_id);
            $doctorName = $doctorUser
                ? trim(($doctorUser->first_name ?? '') . ' ' . ($doctorUser->last_name ?? ''))
                : 'the doctor';
            $body = 'sorry, your appointment with Dr. ' . $doctorName . ' on ' . Carbon::parse($appointment->appointment_time)->format('Y-m-d H:i') . ' has been cancelled due to non-payment.';

            try {
                $this->storeNotification(
                    $appointment->patient_id,
                    'cancellation of your appointment',
                    $body,
                    'appointment_cancelled',
                    ['appointment_id' => $appointment->id]
                );
            } catch (\Exception $storeError) {
                Log::error('Store cancellation notification failed: ' . $storeError->getMessage());
            }

            if (empty($token)) {
                return;
            }

            $messaging = app('firebase.messaging');
            $message = CloudMessage::fromArray([
                'token' => $token,
                'notification' => [
                    'title' => 'cancellation of your appointment',
                    'body' => $body,
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
            // doctor_id stores users.id (Flutter / booking contract)
            $doctorUser = User::find($appointment->doctor_id);
            $doctorName = $doctorUser
                ? trim(($doctorUser->first_name ?? '') . ' ' . ($doctorUser->last_name ?? ''))
                : 'the doctor';
            $formattedTime = Carbon::parse($appointment->appointment_time)->format('h:i A');
            $body = "You have an appointment tomorrow with Dr. {$doctorName} at {$formattedTime}. Tap to confirm and pay remaining amount.";

            try {
                $this->storeNotification(
                    $appointment->patient_id,
                    'Confirm Your Attendance 🔔',
                    $body,
                    'appointment_reminder',
                    [
                        'appointment_id' => $appointment->id,
                        'action_required' => 'confirm_and_pay',
                    ]
                );
            } catch (\Exception $storeError) {
                Log::error('Store reminder notification failed: ' . $storeError->getMessage());
            }

            if (empty($token)) {
                return;
            }

            $messaging = app('firebase.messaging');
            $message = CloudMessage::fromArray([
                'token' => $token,
                'notification' => [
                    'title' => 'Confirm Your Attendance 🔔',
                    'body' => $body,
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