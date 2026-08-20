<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Notification;
use App\Services\AppointmentServices;
use App\Services\FcmService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;

class CancelUnconfirmedAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cancel-unconfirmed-appointments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
      public function handle()
    {
            $appointments = Appointment::where('status', 'pending_deposit')
            ->whereBetween('appointment_time', [now(), now()->addHours(6)])
            ->with(['patient', 'doctor.user']) 
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('No pending appointments found within the 6-hour window.');
            return;
        }

        foreach ($appointments as $appointment) {
                        $appointment->update([
                'status' => 'cancelled'
            ]);
            $patient = $appointment->patient;
            if ($patient && $patient->fcm_token) {
                $this->sendCancellationNotification($patient->fcm_token, $appointment);
            }
        }

        $this->info('Pending appointments checked and cancelled successfully.');
    }


    public function sendCancellationNotification($token, $appointment) {
        try {
            $messaging = app('firebase.messaging');
                        $doctorName = $appointment->doctor->user->first_name . ' ' . $appointment->doctor->user->last_name;
                        $body = 'sorry, your appointment with Dr. ' . $doctorName . ' on ' . $appointment->appointment_time->format('Y-m-d H:i') . ' has been cancelled due to non-payment.';

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

            // تخزين الإشعار بالداتابيس عشان يظهر بداشبورد/تطبيق المريض
            // ملاحظة: هون $appointment->patient->fcm_token مباشرة (مش patient->user->fcm_token
            // متل الكوماند التانية) فافترضت إنو patient هو نفسو الـ User model.
            // إذا مش هيك بمشروعك، بدلي patient->id بـ patient->user->id
            if ($appointment->patient) {
                Notification::create([
                    'user_id' => $appointment->patient->id,
                    'title'   => 'cancellation of your appointment',
                    'body'    => $body,
                    'type'    => 'appointment_cancelled',
                    'data'    => ['appointment_id' => $appointment->id],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Firebase cancellation failed for Appointment ID ' . $appointment->id . ': ' . $e->getMessage());
        }
    }
    }