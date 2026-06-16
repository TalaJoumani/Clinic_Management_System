<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * تابع عام لإرسال إشعار فايربيز باستخدام الـ v1 API الافتراضية
     */
    public function sendPushNotification($fcmToken, $title, $body, $data = [])
    {
        // إذا لم يكن هناك توكن للمستخدم نوقف العملية فوراً لحماية السيستم
        if (!$fcmToken) {
            return false;
        }

        try {
            // 🚨 ملاحظة للمناقشة: يتم جلب الإعدادات من ملف الـ .env
            $projectId = config('services.firebase.project_id'); 
            $accessToken = config('services.firebase.access_token'); // أو استخدام Google Credentials Authentication

            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $response = Http::withToken($accessToken)
                ->post($url, [
                    'message' => [
                        'token' => $fcmToken,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => $data // تمرير داتا إضافية إذا لزم الأمر (مثل id الموعد)
                    ]
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::error("FCM Send Failed: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("FCM Exception: " . $e->getMessage());
            return false;
        }
    }
}