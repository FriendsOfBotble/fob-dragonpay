<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class IncorrectSecretKeyException extends PaymentException
{
    public function construct($message, $code = 102): void
    {
        parent::__construct($message, $code);
    }
}
