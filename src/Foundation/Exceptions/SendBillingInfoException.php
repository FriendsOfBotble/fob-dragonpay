<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class SendBillingInfoException extends PaymentException
{
    public function construct($message, $code = 500): void
    {
        parent::__construct($message, $code);
    }
}
