<?php

namespace App\Models\Concerns;

/**
 * Converts between the application's clock integers (`930` for 09:30) and minutes past midnight.
 */
trait HasClockTimes
{
    /**
     * Minutes past midnight for a clock integer such as `930`.
     */
    public static function minutesFromClock(int $clock): int
    {
        return intdiv($clock, 100) * 60 + $clock % 100;
    }

    /**
     * Clock integer such as `930` for the given minutes past midnight.
     */
    public static function clockFromMinutes(int $minutes): int
    {
        return intdiv($minutes, 60) * 100 + $minutes % 60;
    }

    /**
     * 24-hour label such as `09:30` for a clock integer.
     */
    public static function formatClock(int $clock): string
    {
        return sprintf('%02d:%02d', intdiv($clock, 100), $clock % 100);
    }
}
