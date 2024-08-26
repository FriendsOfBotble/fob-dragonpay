<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class TransactionLimitExceededException extends PaymentException
{
    public function construct($message, $code = 109): void
    {
        parent::__construct($message, $code);
    }
}
