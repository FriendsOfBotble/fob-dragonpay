<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class InvalidMerchantPasswordException extends PaymentException
{
    public function construct($message, $code = 202): void
    {
        parent::__construct($message, $code);
    }
}
