<?php
declare(strict_types=1);

namespace App\Helpers;

use DateTimeImmutable;
use Exception;
use IntlDateFormatter;

final class DateHelper
{
    /**
     * Conversion rapide YYYY-MM-DD → dd/mm
     * (rapide, sans locale)
     */
    public static function toDdMm(string $ymd): string
    {
        if (strlen($ymd) !== 10) {
            return $ymd;
        }

        return substr($ymd, 8, 2) . '/' . substr($ymd, 5, 2);
    }

    /**
     * Conversion locale FR YYYY-MM-DD → dd/MM/yyyy
     * (robuste, locale-aware)
     */
    public static function toFr(string $ymd, string $pattern = 'dd/MM/yyyy'): string
    {
        try {
            $dt = new DateTimeImmutable($ymd);
        } catch (Exception $e) {
            return $ymd;
        }

        $fmt = new IntlDateFormatter(
            'fr_FR',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $dt->getTimezone()->getName(),
            IntlDateFormatter::GREGORIAN,
            $pattern
        );

        return $fmt->format($dt) ?: $ymd;
    }

    public static function toHuman(string $ymd): string
    {
        return self::toFr($ymd, 'EEEE dd MMMM yyyy');
    }
}
