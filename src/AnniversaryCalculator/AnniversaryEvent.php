<?php

namespace LiturgicalCalendar\AnniversaryCalculator;

use LiturgicalCalendar\AnniversaryCalculator\Enums\AnnivType;
use LiturgicalCalendar\AnniversaryCalculator\Enums\AreaInterest;
use LiturgicalCalendar\AnniversaryCalculator\Enums\LitCalendar;

class AnniversaryEvent
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

    public int $event_idx;
    public string $event_key;

    public string $subject;
    public string $anniversary_type;
    public string $anniversary_type_lcl;
    public ?string $anniversary_name;
    public ?int $event_year;
    public ?int $event_month;
    public ?int $event_day;
    public ?int $memorial_month;
    public ?int $memorial_day;
    public string $calendar;
    public string $calendar_lcl;
    public string $calendar_area;
    public ?string $place_of_birth;
    public ?string $place_of_death;
    public ?string $place_of_burial;
    public ?string $main_shrine;
    public ?string $places;
    public array $area_of_interest;
    public array $area_of_interest_lcl;
    public ?string $notes;
    public ?string $anniversary;
    public ?string $anniversary_lcl;
    public ?string $patronage;
    public int $year_diff;
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
        $this->event_idx           = $rowData["event_idx"];
        $this->event_key           = $rowData["event_key"];

        $this->subject               = $rowData["subject"];
        $this->anniversary_type      = $AnnivType->isValid($rowData["anniversary"]) ? strtoupper($rowData["anniversary"]) : '???';
        $this->anniversary_type_lcl  = $AnnivType->i18n($rowData["anniversary"]);
        $this->event_year            = $rowData["event_year"];
        $this->event_month           = $rowData["event_month"];
        $this->event_day             = $rowData["event_day"];
        $this->memorial_month        = $rowData["memorial_month"];
        $this->memorial_day          = $rowData["memorial_day"];
        $this->calendar              = $LitCalendar->isValid($rowData["calendar"]) ? strtoupper($rowData["calendar"]) : '???';
        $this->calendar_lcl          = $LitCalendar->i18n($rowData["calendar"]);
        if (isset($rowData["calendar_area"])) {
            $this->calendar_area = $rowData["calendar_area"];
        }
        $this->place_of_birth        = $rowData["place_of_birth"];
        $this->place_of_death        = $rowData["place_of_death"];
        $this->place_of_burial       = $rowData["place_of_burial"];
        $this->main_shrine           = $rowData["main_shrine"];
        $this->places                = $rowData["places"];
        $this->area_of_interest      = $rowData["area"] ? array_map('strtoupper', explode(",", $rowData["area"])) : [];
        $this->area_of_interest_lcl  = $rowData["area"] ? $AreaInterest->i18n(explode(",", $rowData["area"])) : [];
        $this->notes                 = $rowData["notes"];
        $this->patronage             = $rowData["patronage"];
    }

    public function setAnniversary(int $anniv)
    {
        $this->anniversary = array_search($anniv, self::ANNIVERSARY);
        $this->anniversary_lcl = self::$GTXT[ $this->anniversary ];
    }

    public function setYearDiff(int $yearDiff)
    {
        $this->year_diff = $yearDiff;
    }
}
