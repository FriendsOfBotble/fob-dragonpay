<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class UnauthorizedAccessException extends PaymentException
{
    public function construct($message, $code = 104): void
    {
        parent::__construct($message, $code);
    }
}
