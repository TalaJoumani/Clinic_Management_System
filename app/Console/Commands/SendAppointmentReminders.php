<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AppointmentReminder;
use Kreait\Firebase\Messaging\CloudMessage;
use Carbon\Carbon; // لإصلاح مشكلة الـ format في الوقت

class SendAppointmentReminders extends Command
{
    protected $signature = 'app:send-appointment-reminders';
    protected $description = 'Send appointment reminders to patients 24 hours before their appointment';

    public function handle()
    {
        $now=now();
        $start=$now;
        $end = $now->addHours(24);
        Log::info('current time:'.$now);
        Log::info('Searching for appointments between: ' . $start . ' and ' . $end);
        $appointments = Appointment::whereBetween('appointment_time', [$start->copy()->subMinutes(30), $end->copy()->addMinutes(30)])
            ->whereIn('status', ['confirmed'])
            ->with(['patient', 'doctor.user'])
            ->get();
            Log::info('Found ' . $appointments->count() . ' appointments for tomorrow');

        if ($appointments->isEmpty()) {
            $this->info('No appointments for tomorrow');
            return;
        }

        // تغيير المتغير هنا إلى singular (appointment)
        foreach ($appointments as $appointment) {
            Log::info('Appointment ID:'.$appointment->id.'|Doctor ID: '.$appointment->doctor_id);
            Log::info('Doctor object: '.json_encode($appointment->doctor));
            $patient = $appointment->patient;
        
            if ($patient && $patient->email) {
                Mail::to($patient->email)->send(new AppointmentReminder($appointment));
            }
            
            if ($patient && $patient->token) {
                $this->sendFirebaseNotification($patient->token, $appointment);
            }
        }
        
        $this->info('Appointment reminders sent successfully: ' . $appointments->count());
    }

    private function sendFirebaseNotification($Token, $appointment) {
        try {
            $messaging = app('firebase.messaging');
            $message = CloudMessage::fromArray([
                'token' => $Token,
                'notification' => [
                    'title' => 'Appointment Reminder',
                    'body' => 'You have an appointment with Dr. ' . $appointment->doctor->user->first_name . ' ' . $appointment->doctor->user->last_name . ' at ' . Carbon::parse($appointment->appointment_time)->format('H:i A') . ' tomorrow.',
                ],
            ]);
            $messaging->send($message);
        } catch (\Exception $e) {
            $this->error('Failed to send Firebase notification: ' . $e->getMessage());
        }
    }
}