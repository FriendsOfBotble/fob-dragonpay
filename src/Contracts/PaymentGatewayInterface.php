<?php

namespace FriendsOfBotble\Dragonpay\Contracts;

interface PaymentGatewayInterface
{
    public function setRequestParameters(array $parameters): self;
}
