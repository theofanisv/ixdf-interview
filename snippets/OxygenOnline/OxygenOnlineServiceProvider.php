<?php
/**
 * PHP 7.4, Laravel 8.0
 * This is a ServiceProvider for transimmiting the issued invoices to the greek IRS in real-time and receives the authorized qr code.
 */

namespace App\Packages\OxygenOnline;

use App\Packages\Aade\Contracts\AadeInvoiceMarker;
use App\Packages\OxygenOnline\Commands\InstallOxygenOnline;
use App\Packages\OxygenOnline\Commands\OxygenOnlineMarkDelayed;
use App\Packages\OxygenOnline\Listeners\MarkInvoiceViaOxygenOnlineListener;
use App\Support\Servers\CurrentServer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class OxygenOnlineServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('oxygen-online', OxygenOnline::class);
        $this->app->bind(AadeInvoiceMarker::class, OxygenOnline::class);
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                OxygenOnlineMarkDelayed::class,
                InstallOxygenOnline::class,
            ]);
        }

        $this->registerMacros();
        Event::listen(\App\Events\ChargeSaved::class, MarkInvoiceViaOxygenOnlineListener::class);
        $this->callAfterResolving(Schedule::class, \Closure::fromCallable([$this, 'schedule']));
    }

    private function schedule(Schedule $schedule)
    {
        $schedule->command(OxygenOnlineMarkDelayed::class)
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/cron/OxygenOnlineMarkDelayed-' . now()->toDateString() . '.log'))
            ->when(CurrentServer::isPrimaryOrActiveSecondaryOrSingle())
            ->when(fn() => app('oxygen-online')->hasUnsentInvoices())
            ->whileLoggedIn();
    }

    private function registerMacros()
    {
        Http::macro('oxygenOnline',
            /**
             * URL for Oxygen Online
             * @return \Illuminate\Http\Client\PendingRequest
             */
            function () {
                return Http::acceptJson()
                    ->timeout(10)
                    ->withToken(config('services.oxygen-online.api_key'))
                    ->baseUrl(OxygenOnline::baseUrl());
            });
    }

    public function provides()
    {
        return ['oxygen-online'];
    }
}
