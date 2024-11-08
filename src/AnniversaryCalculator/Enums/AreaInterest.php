<?php

namespace LiturgicalCalendar\AnniversaryCalculator\Enums;

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

    /**
     * Constructor.
     *
     * Initializes the $GTXT property with the localized list of Areas of Interest.
     *
     * @return void
     */
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

    /**
     * Check if the given string is a valid area of interest.
     *
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value)
    {
        return in_array($value, self::$values);
    }

    /**
     * Return true if all the values in the given array are valid area of interest, false otherwise.
     *
     * @param array $values
     * @return bool
     */
    public static function areValid(array $values)
    {
        return empty(array_diff($values, self::$values));
    }

    /**
     * Returns the localized value for the given area of interest.
     *
     * If $value is an array, it will be mapped to the localized values.
     * If the given value is not valid, it will be returned as is.
     *
     * @param string|array $value
     * @return string|array
     */
    public function i18n(string|array $value): string|array
    {
        if (is_array($value)) {
            if (self::areValid($value)) {
                return array_map(array( $this, 'i18n' ), $value);
            }
        } else {
            if (self::isValid($value)) {
                return $this->GTXT[ $value ];
            }
        }
        return $value;
    }
}
