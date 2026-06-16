<?php

namespace App\Console\Commands;

use App\Models\Appointment;
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


    private function sendCancellationNotification($token, $appointment) {
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
    }

