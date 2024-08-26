<?php

namespace FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\Handler;

interface PostbackHandlerInterface
{
    public function handle(array $data): mixed;
}
