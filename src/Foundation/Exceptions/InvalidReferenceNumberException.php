<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class InvalidReferenceNumberException extends PaymentException
{
    public function construct($message, $code = 103): void
    {
        parent::__construct($message, $code);
    }
}
