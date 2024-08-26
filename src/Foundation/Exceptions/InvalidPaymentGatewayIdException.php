<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class InvalidPaymentGatewayIdException extends PaymentException
{
    public function construct($message, $code = 101): void
    {
        parent::__construct($message, $code);
    }
}
