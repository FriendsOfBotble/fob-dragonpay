<?php

namespace FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\DragonPay;

class Token
{
    public function __construct(protected string $token)
    {
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function __toString()
    {
        return $this->token;
    }
}
