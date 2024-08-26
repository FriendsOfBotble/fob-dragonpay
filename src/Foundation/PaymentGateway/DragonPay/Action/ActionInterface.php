<?php

namespace FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\DragonPay\Action;

use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\Dragonpay;

interface ActionInterface
{
    public function doAction(Dragonpay $dragonpay): mixed;
}
