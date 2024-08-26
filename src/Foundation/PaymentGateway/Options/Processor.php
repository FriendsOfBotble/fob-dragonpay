<?php

namespace FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\Options;

use FriendsOfBotble\Dragonpay\Foundation\Exceptions\InvalidProcessIdException;

class Processor
{
    public const GCASH = 'GCSH';

    public const CREDIT_CARD = 'CC';

    public const PAYPAL = 'PYPL';

    public const BAYADCENTER = 'BAYD';

    public const BITCOIN = 'BITC';

    public const CEBUANA_LHUILLIER = 'CEBL';

    public const CHINA_UNIONPAY = 'CUP';

    public const DRAGONPAY_PREPARED_CREDITS = 'DPAY';

    public const ECPAY = 'ECPY';

    public const LBC = 'LBC';

    public const MLHUILLIER = 'MLH';

    public const ROBINSONS_DEPT_STORE = 'RDS';

    public const SM_PAYMENT_COUNTERS = 'SMR';

    public static array $validProcIds = [
        self::GCASH,
        self::CREDIT_CARD,
        self::PAYPAL,
        self::BAYADCENTER,
        self::BITCOIN,
        self::CEBUANA_LHUILLIER,
        self::CHINA_UNIONPAY,
        self::DRAGONPAY_PREPARED_CREDITS,
        self::ECPAY,
        self::LBC,
        self::MLHUILLIER,
        self::ROBINSONS_DEPT_STORE,
        self::SM_PAYMENT_COUNTERS,
    ];

    public static function allowedProcId(string $procid): void
    {
        if (! in_array($procid, static::$validProcIds)) {
            throw new InvalidProcessIdException();
        }
    }
}
