<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class CurrencyNotSupportedException extends PaymentException
{
    public function construct($message, $code = 106): void
    {
        parent::__construct($message, $code);
    }
}
