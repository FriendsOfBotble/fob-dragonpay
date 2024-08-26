<?php

namespace FriendsOfBotble\Dragonpay\Providers;

use Botble\Base\Traits\LoadAndPublishDataTrait;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\Dragonpay;
use Illuminate\Support\ServiceProvider;

class DragonpayServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public const MODULE_NAME = 'dragonpay';

    public function register(): void
    {
        $this->app->singleton(Dragonpay::class, function () {
            return new Dragonpay([
                'merchantid' => get_payment_setting('merchant_id', DragonpayServiceProvider::MODULE_NAME),
                'password' => get_payment_setting('merchant_password', DragonpayServiceProvider::MODULE_NAME),
            ], get_payment_setting('environment', DragonpayServiceProvider::MODULE_NAME) === 'test');
        });
    }

    public function boot(): void
    {
        if (! is_plugin_active('payment')) {
            return;
        }

        $this->setNamespace('plugins/dragonpay')
            ->loadAndPublishTranslations()
            ->loadAndPublishViews()
            ->publishAssets()
            ->loadRoutes();

        $this->app->booted(function () {
            $this->app->register(HookServiceProvider::class);
        });
    }
}
