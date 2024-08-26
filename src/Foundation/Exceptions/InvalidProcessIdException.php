<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

class InvalidProcessIdException extends PaymentException
{
    public function __construct()
    {
        parent::__construct('Invalid Process ID');
    }
}
