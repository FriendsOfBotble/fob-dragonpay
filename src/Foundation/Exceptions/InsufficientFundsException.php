<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class InsufficientFundsException extends PaymentException
{
    public function construct($message, $code = 108): void
    {
        parent::__construct($message, $code);
    }
}
