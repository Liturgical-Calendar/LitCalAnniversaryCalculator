<?php

namespace LitCal\AnniversaryCalculator\Enums;

class LitCalendar
{
    public const UNIVERSAL = "universal";
    public const NATIONAL = "national";
    public const DIOCESAN = "diocesan";
    public const WIDE_AREA = "wide_area";
    //private string $locale;
    private array $GTXT;

    public static array $values = [
        "universal", "national", "diocesan", "wide_area"
    ];

    public function __construct(string $locale)
    {
        //$this->locale = strtoupper( $locale );
        $this->GTXT = [
            self::UNIVERSAL         => strtoupper(_("universal")),
            self::NATIONAL          => strtoupper(_("national")),
            self::DIOCESAN          => strtoupper(_("diocesan")),
            self::WIDE_AREA         => strtoupper(_("wide_area"))
        ];
    }

    public static function isValid(string $value)
    {
        return in_array($value, self::$values);
    }

    public function i18n(string $value): string
    {
        if (self::isValid($value)) {
            return $this->GTXT[ $value ];
        }
        return $value;
    }
}
