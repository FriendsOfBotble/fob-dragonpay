<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class NoAvailablePaymentChannelsException extends PaymentException
{
    public function __construct()
    {
        parent::__construct('No available payment channel.');
    }
}
