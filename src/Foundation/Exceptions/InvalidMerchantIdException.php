<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class InvalidMerchantIdException extends PaymentException
{
    public function construct($message, $code = 201): void
    {
        parent::__construct($message, $code);
    }
}
