<?php

namespace FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\DragonPay\Action;

use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\Dragonpay;
use Illuminate\Support\Facades\Http;

abstract class BaseAction implements ActionInterface
{
    protected string $txnid;

    public function __construct(string $txnid)
    {
        $this->txnid = $txnid;
    }

    protected function getOp(): string
    {
        throw new \Exception('Class {' . get_class($this) . '} does not implement getOp() method.');
    }

    public function doAction(Dragonpay $dragonpay): mixed
    {
        $merchantAccount = $dragonpay->getMerchantAccount();

        $url = str_replace(
            '/Pay.aspx',
            '',
            rtrim($dragonpay->getBaseUrlOf($dragonpay->getPaymentMode()), '/') . '/' . $this->getActionName() . '?op=' . $this->getOp() . '&'
        );

        $parameters = [
            'merchantid' => $merchantAccount['merchantid'],
            'merchantpwd' => $merchantAccount['password'],
            'txnid' => $this->getTransactionId(),
        ];

        $url = sprintf('%s%s', $url, http_build_query($parameters));

        return Http::get($url);
    }

    protected function getTransactionId(): string
    {
        return $this->txnid;
    }

    protected function getActionName(): string
    {
        return 'MerchantRequest.aspx';
    }
}
