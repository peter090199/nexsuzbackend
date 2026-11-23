<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\DataAccess\ClientsDAL;
use App\Business\ClientsBAL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ClientsDAL::class, function ($app) {
            return new ClientsDAL();
        });

        $this->app->bind(ClientsBAL::class, function ($app) {
            return new ClientsBAL($app->make(ClientsDAL::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dynamic SMTP: remember the .env mail config, then apply any settings saved from the admin UI the first time
        // the mail manager is used in a request (so a saved change takes effect immediately, with no restart).
        \App\Services\MailConfigService::snapshotEnv();
        $this->app->resolving('mail.manager', function () {
            try {
                app(\App\Services\MailConfigService::class)->apply();
            } catch (\Throwable $e) {
                report($e);   // a broken saved config must never take mail down: .env stays in effect
            }
        });

        // "Last successful email": any message the app really sends through the saved SMTP settings
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Mail\Events\MessageSent::class, function () {
            try {
                $svc = app(\App\Services\MailConfigService::class);
                if ($svc->tableReady() && $svc->stored()) {
                    $svc->saveStatus(['last_success_at' => now()->toDateTimeString(), 'last_error' => null]);
                }
            } catch (\Throwable) {
                // status bookkeeping must never break sending
            }
        });
    }
}
