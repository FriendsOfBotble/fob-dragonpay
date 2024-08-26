<?php

namespace FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\DragonPay\Action;

use FriendsOfBotble\Dragonpay\Foundation\Exceptions\Action\CancelTransactionException;
use FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\Dragonpay;

class CancelTransaction extends BaseAction
{
    public function doAction(Dragonpay $dragonpay): bool
    {
        $result = parent::doAction($dragonpay);

        if ($result == 0) {
            return true;
        }

        throw new CancelTransactionException();
    }

    protected function getOp(): string
    {
        return 'VOID';
    }
}
