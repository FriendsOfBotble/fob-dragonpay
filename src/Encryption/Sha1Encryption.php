<?php

namespace FriendsOfBotble\Dragonpay\Encryption;

use FriendsOfBotble\Dragonpay\Contracts\DigestInterface;

class Sha1Encryption implements DigestInterface
{
    public function make(array $data): string
    {
        return sha1(implode(':', $data));
    }
}
