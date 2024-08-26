<?php

namespace FriendsOfBotble\Dragonpay\Providers;

use Botble\Base\Facades\Html;
use Botble\Ecommerce\Models\Currency;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Facades\PaymentMethods;
use Botble\Payment\Models\Payment;
use Exception;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\Dragonpay;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\DragonPay\Action\CheckTransactionStatus;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\DragonPay\Token;
use FriendsOfBotble\Dragonpay\Services\DragonpayPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, function (?string $settings) {
            $name = 'Dragonpay';
            $description = trans('plugins/dragonpay::dragonpay.description');
            $link = 'https://www.dragonpay.ph';
            $image = asset('vendor/core/plugins/dragonpay/images/dragonpay.png');
            $moduleName = DragonpayServiceProvider::MODULE_NAME;
            $status = (bool) get_payment_setting('status', $moduleName);

            return $settings . view(
                'plugins/dragonpay::settings',
                compact('name', 'description', 'link', 'image', 'moduleName', 'status')
            )->render();
        }, 999);

        add_filter(BASE_FILTER_ENUM_ARRAY, function (array $values, string $class): array {
            if ($class === PaymentMethodEnum::class) {
                $values['DRAGONPAY'] = DragonpayServiceProvider::MODULE_NAME;
            }

            return $values;
        }, 999, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class): string {
            if ($class === PaymentMethodEnum::class && $value === DragonpayServiceProvider::MODULE_NAME) {
                $value = 'Dragonpay';
            }

            return $value;
        }, 999, 2);

        add_filter(BASE_FILTER_ENUM_HTML, function (string $value, string $class): string {
            if ($class === PaymentMethodEnum::class && $value === DragonpayServiceProvider::MODULE_NAME) {
                $value = Html::tag(
                    'span',
                    PaymentMethodEnum::getLabel($value),
                    ['class' => 'label-success status-label']
                )->toHtml();
            }

            return $value;
        }, 999, 2);

        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, function (?string $html, array $data): ?string {
            if (get_payment_setting('status', DragonpayServiceProvider::MODULE_NAME)) {
                $supportedCurrencies = $this->app->make(DragonpayPaymentService::class)->getSupportedCurrencies();

                $currencies = get_all_currencies()
                    ->filter(fn (Currency $currency) => in_array($currency->title, $supportedCurrencies));

                PaymentMethods::method(DragonpayServiceProvider::MODULE_NAME, [
                    'html' => view(
                        'plugins/dragonpay::method',
                        array_merge($data, [
                            'moduleName' => DragonpayServiceProvider::MODULE_NAME,
                            'supportedCurrencies' => $supportedCurrencies,
                            'currencies' => $currencies,
                        ]),
                    )->render(),
                ]);
            }

            return $html;
        }, 999, 2);

        add_filter(PAYMENT_FILTER_GET_SERVICE_CLASS, function (?string $data, string $value): ?string {
            if ($value === DragonpayServiceProvider::MODULE_NAME) {
                $data = DragonpayPaymentService::class;
            }

            return $data;
        }, 20, 2);

        add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, function (array $data, Request $request): array {
            if ($data['type'] !== DragonpayServiceProvider::MODULE_NAME) {
                return $data;
            }

            $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);

            $supportedCurrencies = $this->app->make(DragonpayPaymentService::class)->getSupportedCurrencies();

            if (! in_array($paymentData['currency'], $supportedCurrencies)) {
                $data['error'] = true;
                $data['message'] = __(":name doesn't support :currency. List of currencies supported by :name: :currencies.", ['name' => 'Dragonpay', 'currency' => $data['currency'], 'currencies' => implode(', ', $supportedCurrencies)]);

                return $data;
            }

            try {
                $dragonpay = $this->app->make(Dragonpay::class);

                $token = $dragonpay->getToken([
                    'txnid' => Str::random(40),
                    'amount' => number_format($paymentData['amount'], 2, '.', ''),
                    'ccy' => $paymentData['currency'],
                    'description' => $paymentData['description'],
                    'email' => $paymentData['address']['email'],
                    'param1' => json_encode([
                        'order_id' => $paymentData['order_id'],
                        'token' => $paymentData['checkout_token'],
                    ]),
                    'param2' => json_encode([
                        'customer_id' => $paymentData['customer_id'],
                        'customer_type' => $paymentData['customer_type'],
                    ]),
                    'firstName' => Str::of($paymentData['address']['name'])->before(' ')->toString(),
                    'lastName' => Str::of($paymentData['address']['name'])->after(' ')->toString(),
                    'address1' => $paymentData['address']['address'],
                    'city' => $paymentData['address']['city'],
                    'state' => $paymentData['address']['state'],
                    'country' => $paymentData['address']['country'],
                    'zipCode' => $paymentData['address']['zip_code'],
                    'telNo' => $paymentData['address']['phone'],
                ]);
                if ($token instanceof Token) {
                    $dragonpay->away();
                }
            } catch (Exception $exception) {
                $data['error'] = true;
                $data['message'] = json_encode($exception->getMessage());
            }

            return $data;
        }, 999, 2);

        add_filter(PAYMENT_FILTER_PAYMENT_INFO_DETAIL, function (string $data, Payment $payment) {
            if ($payment->payment_channel != DragonpayServiceProvider::MODULE_NAME) {
                return $data;
            }

            $dragonpay = $this->app->make(Dragonpay::class);
            $detail = $dragonpay->action(new CheckTransactionStatus($payment->charge_id));
            $payment['detail'] = $detail;

            return view('plugins/dragonpay::detail', compact('payment')) . $data;
        }, 999, 2);
    }
}
