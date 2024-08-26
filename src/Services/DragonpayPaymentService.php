<?php

namespace FriendsOfBotble\Dragonpay\Services;

use Botble\Payment\Enums\PaymentStatusEnum;
use Exception;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\Dragonpay;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\DragonPay\Action\CheckTransactionStatus;
use FriendsOfBotble\Dragonpay\Providers\DragonpayServiceProvider;
use Illuminate\Support\Arr;

class DragonpayPaymentService extends PaymentServiceAbstract
{
    public function isSupportRefundOnline(): bool
    {
        return false;
    }

    public function getSupportedCurrencies(): array
    {
        return [
            'PHP',
            'USD',
            'CAD',
        ];
    }

    public function refund(string $chargeId, float $amount): array
    {
        return [];
    }

    public function afterMakePayment(string $chargeId): bool
    {
        try {
            $data = app(Dragonpay::class)->action(new CheckTransactionStatus($chargeId));

            $status = match ($data['Status']) {
                Dragonpay::SUCCESS => PaymentStatusEnum::COMPLETED,
                Dragonpay::FAILED => PaymentStatusEnum::FAILED,
                default => PaymentStatusEnum::PENDING,
            };

            $param1 = json_decode($data['Param1'], true);
            $customer = json_decode($data['Param2'], true);

            do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                'amount' => $data['Amount'],
                'currency' => $data['Currency'],
                'charge_id' => $data['TxnId'],
                'payment_channel' => DragonpayServiceProvider::MODULE_NAME,
                'status' => $status,
                'customer_id' => Arr::get($customer, 'customer_id'),
                'customer_type' => Arr::get($customer, 'customer_type'),
                'payment_type' => 'direct',
                'order_id' => Arr::get($param1, 'order_id'),
            ], $data);
        } catch (Exception $exception) {
            logger()->error($exception->getMessage());

            $this->setErrorMessage($exception->getMessage());

            return false;
        }

        return true;
    }
}
