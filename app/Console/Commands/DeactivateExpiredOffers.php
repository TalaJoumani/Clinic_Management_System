<?php

namespace App\Console\Commands;

use App\Models\Offer;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeactivateExpiredOffers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:deactivate-expired-offers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate Offers whose vaild_until date has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now=Carbon::now();
        $affected=Offer::where('is_active','true')
        ->whereDate('valid_until','<',$now)
        ->update(['is_active'=>false]);
        $this->info("successfuly deactivated {$affected} expired offers");
    }
}
