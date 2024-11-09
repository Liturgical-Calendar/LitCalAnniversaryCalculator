<?php

namespace LiturgicalCalendar\AnniversaryCalculator\Enums;

class AnnivType
{
    public const BIRTH                  = 'birth';
    public const DEATH                  = 'death';
    public const CANONIZATION           = 'canonization';
    public const DOCTOR                 = 'doctor';
    public const DEDICATION             = 'dedication';
    public const TRANSLATION            = 'translation';
    public const DOGMA                  = 'dogma';
    public const APPARITION             = 'apparition';
    public const PONTIFICAL_INCORONATION = 'pontifical_incoronation';
    public const ECUMENICAL_COUNCIL     = 'ecumenical_council';
    public const CHURCH_COUNCIL         = 'church_council';
    public const IMPERIAL_EDICT         = 'imperial_edict';
    public const ENCYCLICAL             = 'encyclical';
    public const OTHER                  = 'other';
    private array $GTXT;

    public static array $values = [
        'birth',
        'death',
        'canonization',
        'doctor',
        'dedication',
        'translation',
        'dogma',
        'apparition',
        'pontifical_incoronation',
        'ecumenical_council',
        'church_council',
        'imperial_edict',
        'encyclical',
        'other'
    ];

    public function __construct()
    {
        $this->GTXT = [
            self::BIRTH                     => strtoupper(_('birth')),
            self::DEATH                     => strtoupper(_('death')),
            self::CANONIZATION              => strtoupper(_('canonization')),
            self::DOCTOR                    => strtoupper(_('doctor')),
            self::DEDICATION                => strtoupper(_('dedication')),
            /**translators: the term 'translation' refers to the transferral of the relics of a saint */
            self::TRANSLATION               => strtoupper(_('translation')),
            self::DOGMA                     => strtoupper(_('dogma')),
            self::APPARITION                => strtoupper(_('apparition')),
            self::PONTIFICAL_INCORONATION   => strtoupper(_('pontifical incoronation')),
            self::ECUMENICAL_COUNCIL        => strtoupper(_('ecumenical council')),
            self::CHURCH_COUNCIL            => strtoupper(_('church council')),
            self::IMPERIAL_EDICT            => strtoupper(_('imperial edict')),
            self::ENCYCLICAL                => strtoupper(_('encyclical')),
            self::OTHER                     => strtoupper(_('other'))
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
