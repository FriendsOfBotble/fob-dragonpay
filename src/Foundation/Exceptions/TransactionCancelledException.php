<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class TransactionCancelledException extends PaymentException
{
    public function construct($message, $code = 107): void
    {
        parent::__construct($message, $code);
    }
}
