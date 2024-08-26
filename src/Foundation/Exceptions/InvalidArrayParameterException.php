<?php

namespace FriendsOfBotble\Dragonpay\Foundation\Exceptions;

final class InvalidArrayParameterException extends PaymentException
{
    public function construct($message, $code = 400): void
    {
        parent::__construct($message, $code);
    }

    public static function invalidArrayKey(): InvalidArrayParameterException
    {
        return new InvalidArrayParameterException('Missing required array key/s. Please check your key/s.', 400);
    }

    public static function sendBillingInfoParameters(): InvalidArrayParameterException
    {
        return new InvalidArrayParameterException(
            'Missing required array key/s. Please check your parameters when using credit card payment mode.',
            400
        );
    }
}
