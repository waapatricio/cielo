<?php

namespace Waapatricio\Cielo;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Lunar\Facades\Payments;
// use Waapatricio\Cielo\Components\PaymentForm;
// use Waapatricio\Cielo\Managers\StripeManager;

class CieloPaymentsServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Register our payment type.
        Payments::extend('cielo', function ($app) {
            return $app->make(CieloPaymentType::class);
        });

        // $this->app->singleton('gc:stripe', function ($app) {
        //     return $app->make(StripeManager::class);
        // });

        // Blade::directive('stripeScripts', function () {
        //     return  <<<'EOT'
        //         <script src="https://js.stripe.com/v3/"></script>
        //     EOT;
        // });

        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'lunar');

        $this->mergeConfigFrom(__DIR__.'/../config/cielo.php', 'cielo');

        // $this->publishes([
        //     __DIR__.'/../config/stripe.php' => config_path('lunar/stripe.php'),
        // ], 'lunar.stripe.config');

        // $this->publishes([
        //     __DIR__.'/../resources/views' => resource_path('views/vendor/lunar'),
        // ], 'lunar.stripe.components');

        // Register the stripe payment component.
        Livewire::component('cielo.payment', PaymentForm::class);
    }
}
