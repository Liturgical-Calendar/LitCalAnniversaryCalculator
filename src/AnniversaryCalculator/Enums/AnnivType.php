<?php

namespace LitCal\AnniversaryCalculator\Enums;

class AnnivType
{
    public const BIRTH = "birth";
    public const DEATH = "death";
    public const CANONIZATION = "canonization";
    public const DOCTOR = "doctor";
    public const DEDICATION = "dedication";
    public const TRANSLATION = "translation";
    public const DOGMA = "dogma";
    public const OTHER = "other";
    private array $GTXT;

    public static array $values = [
        "birth", "death", "canonization", "doctor", "dedication", "translation", "dogma", "other"
    ];

    public function __construct()
    {
        $this->GTXT = [
            self::BIRTH         => strtoupper(_("birth")),
            self::DEATH         => strtoupper(_("death")),
            self::CANONIZATION  => strtoupper(_("canonization")),
            self::DOCTOR        => strtoupper(_("doctor")),
            self::DEDICATION    => strtoupper(_("dedication")),
            /**translators: term "translation" refers to the transferral of the relics of a saint */
            self::TRANSLATION   => strtoupper(_("translation")),
            self::DOGMA         => strtoupper(_("dogma")),
            self::OTHER         => strtoupper(_("other"))
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
