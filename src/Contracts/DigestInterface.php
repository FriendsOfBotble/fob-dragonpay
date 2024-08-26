<?php

namespace FriendsOfBotble\Dragonpay\Contracts;

interface DigestInterface
{
    public function make(array $data): mixed;
}
