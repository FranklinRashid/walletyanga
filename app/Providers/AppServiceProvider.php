<?php

namespace App\Providers;

use App\Domain\Cards\CardIssuerProvider;
use App\Domain\Cards\SandboxCardIssuerProvider;
use App\Domain\Payments\PaychanguClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaychanguClient::class, function (): PaychanguClient {
            return new PaychanguClient(
                secretKey: config('services.paychangu.secret_key'),
                webhookSecret: config('services.paychangu.webhook_secret'),
            );
        });

        $this->app->bind(CardIssuerProvider::class, SandboxCardIssuerProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
