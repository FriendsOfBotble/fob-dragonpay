<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class ErrorInOperationException extends PaymentException
{
    public function construct($message, $code = 110): void
    {
        parent::__construct($message, $code);
    }
}
