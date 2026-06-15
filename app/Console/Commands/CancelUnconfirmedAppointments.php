<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use Illuminate\Console\Command;

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
        $appointments=Appointment::where('appointment_time','<',now()->addHours(6))->where('status','pending_deposit')->get();
        foreach($appointments as $appointment){
            $appointment->update(['status'=>'cancelled']);
        }
    }
}
