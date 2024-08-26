<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class InvalidTokenException extends PaymentException
{
    public function construct($message, $code = 105)
    {
        parent::__construct($message, $code);
    }
}
