<?php

namespace LitCal\AnniversaryCalculator\Enums;

class AreaInterest
{
    public const ROME      = "rome";       // ROMA CRISTIANA
    public const ITALY     = "italy";      // ITALIA CRISTIANA
    public const WORLD     = "world";      // IRC
    public const BIBLICAL  = "biblical";   // BIBLICI
    public const MARIAN    = "marian";     // MARIANI
    private array $GTXT;

    public static array $values = [
        "rome", "italy", "world", "biblical", "marian"
    ];

    public function __construct()
    {
        $this->GTXT = [
            self::ROME          => strtoupper(_("rome")),
            self::ITALY         => strtoupper(_("italy")),
            self::WORLD         => strtoupper(_("world")),
            self::BIBLICAL      => strtoupper(_("biblical")),
            self::MARIAN        => strtoupper(_("marian"))
        ];
    }

    public static function isValid(string $value)
    {
        return in_array($value, self::$values);
    }

    public static function areValid(array $values)
    {
        return empty(array_diff($values, self::$values));
    }

    public function i18n(string|array $value): string|array
    {
        if (is_array($value) && self::areValid($value)) {
            return array_map(array( $this, 'i18n' ), $value);
        } else {
            if (self::isValid($value)) {
                return $this->GTXT[ $value ];
            }
        }
        return $value;
    }
}
