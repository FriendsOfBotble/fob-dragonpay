<?php

namespace FriendsOfBotble\Dragonpay\Foundation\PaymentGateway;

class RequestBag
{
    public static array $requestParameters = [];

    public function __construct(array $request = [])
    {
        self::$requestParameters = $request;
    }

    public function getRequestParams(): array
    {
        return self::$requestParameters;
    }

    public function getParameters(): array
    {
        return $this->getRequestParams();
    }
}
