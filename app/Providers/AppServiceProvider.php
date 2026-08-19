<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Mail::extend('gmail', function () {
            $transportClass = 'App\\Mail\\Transport\\GmailApiTransport';

            return new $transportClass(
                config('services.gmail.client_id'),
                config('services.gmail.client_secret'),
                config('services.gmail.refresh_token'),
            );
        });
    }
}