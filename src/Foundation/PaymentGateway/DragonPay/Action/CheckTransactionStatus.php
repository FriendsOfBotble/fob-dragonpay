<?php

namespace FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\DragonPay\Action;

use Closure;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\Dragonpay;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class CheckTransactionStatus extends BaseAction
{
    public function doAction(Dragonpay $dragonpay): mixed
    {
        $callback = $this->getClosure($dragonpay);

        /** @var Response $response */
        $response = $callback('txnid');

        if ($response->ok()) {
            return $response->json();
        }

        /** @var Response $response */
        $response = $callback('refno');

        if ($response->ok()) {
            return $response->json();
        }

        return null;
    }

    protected function getClosure(Dragonpay $dragonpay): Closure
    {
        $merchantAccount = $dragonpay->getMerchantAccount();

        $url = str_replace(
            '/Pay.aspx',
            '',
            rtrim($dragonpay->getBaseUrlOf($dragonpay->getPaymentMode()), '/')
        );

        $parameters = [
            'merchantid' => $merchantAccount['merchantid'],
            'merchantpwd' => $merchantAccount['password'],
            'txnid' => $this->getTransactionId(),
        ];

        return fn (string $endpoint) => Http::withBasicAuth($parameters['merchantid'], $parameters['merchantpwd'])
            ->get("$url/api/collect/v1/$endpoint/{$parameters['txnid']}");
    }

    protected function getOp(): string
    {
        return 'GETSTATUS';
    }
}
