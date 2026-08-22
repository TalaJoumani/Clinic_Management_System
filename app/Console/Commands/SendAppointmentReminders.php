<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AppointmentReminder;
use Kreait\Firebase\Messaging\CloudMessage;
use Carbon\Carbon;

class SendAppointmentReminders extends Command
{
    protected $signature = 'app:send-appointment-reminders';
    protected $description = 'Send informational emails and interactive Firebase notifications 24 hours before the appointment';

    public function handle()
    {
        $now = now();
        $start = $now->copy()->addHours(24)->subMinutes(30);
        $end = $now->copy()->addHours(24)->addMinutes(30);

        Log::info('Reminder Cron Job Started at: ' . $now);
        
        $appointments = Appointment::whereBetween('appointment_time', [$start, $end])
            ->where('status', 'confirmed') 
            ->with(['patient', 'doctor'])
            ->get();

        if ($appointments->isEmpty()) {
            Log::info('No appointments found for tomorrow in this time slot.');
            return;
        }

        foreach ($appointments as $appointment) {
            $patient = $appointment->patient;
            if ($patient && $patient->email) {
                try {
                    Mail::to($patient->email)->send(new AppointmentReminder($appointment));
                } catch (\Exception $e) {
                    Log::error('Mail failed for Appointment ID ' . $appointment->id . ': ' . $e->getMessage());
                }
            }
            
            if ($patient && $patient->fcm_token) {
                $this->sendFirebaseNotification($patient->fcm_token, $appointment);
            } else {
                Log::info('No fcm_token for patient ' . ($patient ? $patient->id : 'unknown') . ', storing reminder notification only.');
                $this->sendFirebaseNotification(null, $appointment);
            }
        }
        
        $this->info('Reminders processed successfully.');
    }

    public function sendFirebaseNotification($token, $appointment) {
        try {
            $appointment->load('doctor');
            $doctorName = $appointment->doctor ? ($appointment->doctor->first_name . ' ' . $appointment->doctor->last_name) : 'the doctor';
            $formattedTime = Carbon::parse($appointment->appointment_time)->format('h:i A');
            $body = "You have an appointment tomorrow with Dr. {$doctorName} at {$formattedTime}. Tap to confirm and pay remaining amount.";

            // تخزين الإشعار بالداتابيس عشان يظهر بتطبيق المريض بغض النظر عن نجاح FCM
            try {
                if ($appointment->patient) {
                    Notification::create([
                        'user_id' => $appointment->patient->id,
                        'title'   => 'Confirm Your Attendance 🔔',
                        'body'    => $body,
                        'type'    => 'appointment_reminder',
                        'data'    => ['appointment_id' => $appointment->id],
                    ]);
                }
            } catch (\Exception $storeError) {
                Log::error('Store reminder notification failed: ' . $storeError->getMessage());
            }

            if (!$token) {
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