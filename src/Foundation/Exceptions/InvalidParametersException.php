<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class InvalidParametersException extends PaymentException
{
    public function construct($message, $code = 111): void
    {
        parent::__construct($message, $code);
    }
}
