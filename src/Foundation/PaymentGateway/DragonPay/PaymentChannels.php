<?php

namespace FriendsOfBotble\Dragonpay\Foundation\PaymentGateway\DragonPay;

class PaymentChannels
{
    public const EVERYDAY = 'XXXXXXX';

    public const WEEKDAYS   = '0XXXXX0';

    public const WEEKENDS_ONLY = 'X00000X';

    public const SUNDAY_TO_FRIDAY = 'XXXXXX0';

    public const MONDAY_TO_SATURDAY = '0XXXXXX';

    public function everyDay(string $dayOfWeek): bool
    {
        return self::EVERYDAY === $dayOfWeek;
    }

    public function weekDays(string $daysOfWeek): bool
    {
        return self::WEEKDAYS === $daysOfWeek;
    }

    public function weekEnds(string $daysOfWeek): bool
    {
        return self::WEEKENDS_ONLY === $daysOfWeek;
    }

    public function sundayToFriday(string $daysOfWeek): bool
    {
        return self::SUNDAY_TO_FRIDAY === $daysOfWeek;
    }

    public function mondayToSaturday(string $daysOfWeek): bool
    {
        return self::MONDAY_TO_SATURDAY === $daysOfWeek;
    }
}
