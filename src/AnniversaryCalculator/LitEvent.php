<?php

namespace LitCal\AnniversaryCalculator;

use LitCal\AnniversaryCalculator\Enums\AnnivType;
use LitCal\AnniversaryCalculator\Enums\AreaInterest;
use LitCal\AnniversaryCalculator\Enums\LitCalendar;

class LitEvent
{
    public const ANNIVERSARY = [
        "CENTENARY"     => 100,
        "ONYX"          => 95,
        "GRANITE"       => 90,
        "MARBLE"        => 85,
        "OAK"           => 80,
        "PLATINUM"      => 75,
        "IRON"          => 70,
        "STONE"         => 65,
        "DIAMOND"       => 60,
        "EMERALD"       => 55,
        "GOLD"          => 50,
        "SAPPHIRE"      => 45,
        "RUBY"          => 40,
        "CORAL"         => 35,
        "PEARL"         => 30,
        "SILVER"        => 25,
        "PORCELAIN"     => 20,
        "CRISTAL"       => 15,
        "ALUMINUM"      => 10,
        "WOOD"          => 5,
        "PAPER"         => 1
    ];

    public int $idx;
    public string $tag;

    public string $subject;
    public string $anniversaryType;
    public string $anniversaryTypeLcl;
    public ?string $anniversaryName;
    public ?int $year;
    public ?int $eventMonth;
    public ?int $eventDay;
    public ?int $memorialMonth;
    public ?int $memorialDay;
    public string $calendar;
    public string $calendarLcl;
    public ?string $placeOfBirth;
    public ?string $placeOfDeath;
    public ?string $placeOfBurial;
    public ?string $mainShrine;
    public ?string $places;
    public array $areaOfInterest;
    public array $areaOfInterestLcl;
    public ?string $notes;
    public ?string $anniversary;
    public ?string $anniversaryLcl;
    public ?string $patronage;
    public int $yearDiff;
    private static array $GTXT = [];

    public function __construct(array $rowData)
    {
        if (count(self::$GTXT) === 0) {
            self::$GTXT = [
                "CENTENARY"     => _("CENTENARY"),
                "ONYX"          => _("ONYX"),
                "GRANITE"       => _("GRANITE"),
                "MARBLE"        => _("MARBLE"),
                "OAK"           => _("OAK"),
                "PLATINUM"      => _("PLATINUM"),
                "IRON"          => _("IRON"),
                "STONE"         => _("STONE"),
                "DIAMOND"       => _("DIAMOND"),
                "EMERALD"       => _("EMERALD"),
                "GOLD"          => _("GOLD"),
                "SAPPHIRE"      => _("SAPPHIRE"),
                "RUBY"          => _("RUBY"),
                "CORAL"         => _("CORAL"),
                "PEARL"         => _("PEARL"),
                "SILVER"        => _("SILVER"),
                "PORCELAIN"     => _("PORCELAIN"),
                "CRISTAL"       => _("CRISTAL"),
                "ALUMINUM"      => _("ALUMINUM"),
                "WOOD"          => _("WOOD"),
                "PAPER"         => _("PAPER")
            ];
        }
        $AnnivType                 = new AnnivType();
        $AreaInterest              = new AreaInterest();
        $LitCalendar               = new LitCalendar();
        $this->idx                 = $rowData["IDX"];
        $this->tag                 = $rowData["TAG"];

        $this->subject             = $rowData["SUBJECT"];
        $this->anniversaryType     = $AnnivType->isValid($rowData["ANNIVERSARY"]) ? strtoupper($rowData["ANNIVERSARY"]) : '???';
        $this->anniversaryTypeLcl  = $AnnivType->i18n($rowData["ANNIVERSARY"]);
        $this->year                = $rowData["YEAR"];
        $this->eventMonth          = $rowData["EVENT_MONTH"];
        $this->eventDay            = $rowData["EVENT_DAY"];
        $this->memorialMonth       = $rowData["MEMORIAL_MONTH"];
        $this->memorialDay         = $rowData["MEMORIAL_DAY"];
        $this->calendar            = $LitCalendar->isValid($rowData["CALENDAR"]) ? strtoupper($rowData["CALENDAR"]) : '???';
        $this->calendarLcl         = $LitCalendar->i18n($rowData["CALENDAR"]);
        $this->placeOfBirth        = $rowData["PLACE_OF_BIRTH"];
        $this->placeOfDeath        = $rowData["PLACE_OF_DEATH"];
        $this->placeOfBurial       = $rowData["PLACE_OF_BURIAL"];
        $this->mainShrine          = $rowData["MAIN_SHRINE"];
        $this->places              = $rowData["PLACES"];
        $this->areaOfInterest      = $rowData["AREA"] ? array_map('strtoupper', explode(",", $rowData["AREA"])) : [];
        $this->areaOfInterestLcl   = $rowData["AREA"] ? $AreaInterest->i18n(explode(",", $rowData["AREA"])) : [];
        $this->notes               = $rowData["NOTES"];
        $this->patronage           = $rowData["PATRONAGE"];
    }

    public function setAnniversary(int $anniv)
    {
        $this->anniversary = array_search($anniv, self::ANNIVERSARY);
        $this->anniversaryLcl = self::$GTXT[ $this->anniversary ];
    }

    public function setYearDiff(int $yearDiff)
    {
        $this->yearDiff = $yearDiff;
    }
}
