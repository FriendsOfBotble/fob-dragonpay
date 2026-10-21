<?php

namespace FriendsOfBotble\Dragonpay\Services;

use Botble\Payment\Enums\PaymentStatusEnum;
use Exception;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\Dragonpay;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\DragonPay\Action\CheckTransactionStatus;
use FriendsOfBotble\Dragonpay\Providers\DragonpayServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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

    /**
     * param1/param2 travel through the customer's browser unsigned, so the transaction id
     * carries an HMAC of the amount, currency and both params: nonce (8) + HMAC (32) = 40 chars.
     */
    public static function makeSignedTxnId(string $amount, string $currency, string $param1, string $param2): string
    {
        $nonce = Str::random(8);

        return $nonce . static::signature($nonce, $amount, $currency, $param1, $param2);
    }

    public static function hasValidTxnId(array $transaction): bool
    {
        $txnId = (string) Arr::get($transaction, 'TxnId');

        if (strlen($txnId) !== 40) {
            return false;
        }

        $expected = static::signature(
            substr($txnId, 0, 8),
            number_format((float) Arr::get($transaction, 'Amount'), 2, '.', ''),
            (string) Arr::get($transaction, 'Currency'),
            (string) Arr::get($transaction, 'Param1'),
            (string) Arr::get($transaction, 'Param2')
        );

        return hash_equals($expected, substr($txnId, 8));
    }

    protected static function signature(string $nonce, string $amount, string $currency, string $param1, string $param2): string
    {
        return substr(
            hash_hmac('sha256', implode('|', [$nonce, $amount, strtoupper($currency), $param1, $param2]), (string) config('app.key')),
            0,
            32
        );
    }

    public function afterMakePayment(string $chargeId): bool
    {
        try {
            $data = app(Dragonpay::class)->action(new CheckTransactionStatus($chargeId));

            if (! is_array($data) || ! static::hasValidTxnId($data)) {
                throw new Exception('Dragonpay transaction ' . $chargeId . ' does not match the checkout it was created for.');
            }

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
