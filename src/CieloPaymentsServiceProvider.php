<?php

namespace Waapatricio\Cielo;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Lunar\Facades\Payments;
use Waapatricio\Cielo\Components\PaymentForm;
// use Waapatricio\Cielo\Managers\StripeManager;

class CieloPaymentsServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {

        Payments::extend('cielo', function ($app) {
            return $app->make(CieloPaymentType::class);
        });

        $this->mergeConfigFrom(__DIR__.'/../config/cielo.php','cielo');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Blade::directive('stripeScripts', function () {
        //     return  <<<'EOT'
        //         <script src="https://js.stripe.com/v3/"></script>
        //     EOT;
        // });

        $this->loadViewsFrom(__DIR__.'/../resources/views','cielo');

        // $this->publishes([
        //     __DIR__.'/../config/cielo.php' => config_path('cielo.php'),
        // ], 'cielo.config');

        $this->publishes([
            __DIR__.'/../config/cielo.php' => config_path('cielo.php'),
        ], 'cielo.config');

        // $this->publishes([
        //     __DIR__.'/../resources/views' => resource_path('views/vendor/lunar'),
        // ], 'cielo.components');

        // Register the cielo payment component.
        Livewire::component('cielo-payment', PaymentForm::class);
    }
}
