<?php

namespace App\Providers;

use App\Domain\Cards\CardIssuerProvider;
use App\Domain\Cards\SandboxCardIssuerProvider;
use App\Domain\Cards\SudoAfricaCardIssuerProvider;
use App\Domain\Payments\PaychanguClient;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaychanguClient::class, function (): PaychanguClient {
            return new PaychanguClient(
                publicKey: config('services.paychangu.public_key'),
                secretKey: config('services.paychangu.secret_key'),
                webhookSecret: config('services.paychangu.webhook_secret'),
            );
        });

        $this->app->bind(CardIssuerProvider::class, function () {
            return match (config('services.cards.issuer', 'sandbox')) {
                'sudo', 'sudo_africa' => app(SudoAfricaCardIssuerProvider::class),
                default => app(SandboxCardIssuerProvider::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            $destructiveCommands = [
                'db:wipe',
                'migrate:fresh',
                'migrate:refresh',
                'migrate:reset',
            ];

            if (! in_array($event->command, $destructiveCommands, true)) {
                return;
            }

            if (! $this->app->environment('local')) {
                return;
            }

            if (config('database.default') !== 'mysql') {
                return;
            }

            if ((bool) env('ALLOW_DESTRUCTIVE_DB_COMMANDS', false)) {
                return;
            }

            throw new RuntimeException(
                "Blocked '{$event->command}' because the local app is using MySQL. ".
                'Use php artisan migrate to preserve data, or set ALLOW_DESTRUCTIVE_DB_COMMANDS=true only if you intentionally want to wipe the database.'
            );
        });
    }
}
