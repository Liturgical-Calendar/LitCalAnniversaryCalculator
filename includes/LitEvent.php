<?php
include_once( 'includes/enums/AnnivType.php' );
include_once( 'includes/enums/AreaInterest.php' );
include_once( 'includes/enums/LitCalendar.php' );

class LitEvent {

    const ANNIVERSARIO = [
        "CENTENARIO"    => 100,
        "ONICE"         => 95,
        "GRANITO"       => 90,
        "MARMO"         => 85,
        "QUERCIA"       => 80,
        "PLATINO"       => 75,
        "FERRO"         => 70,
        "PIETRA"        => 65,
        "DIAMANTE"      => 60,
        "SMERALDO"      => 55,
        "ORO"           => 50,
        "ZAFFIRO"       => 45,
        "RUBINO"        => 40,
        "CORALLO"       => 35,
        "PERLA"         => 30,
        "ARGENTO"       => 25,
        "PORCELLANA"    => 20,
        "CRISTALLO"     => 15,
        "STAGNO"        => 10,
        "LEGNO"         => 5,
        "CARTA"         => 1
    ];

    const ANNIVERSARY = [
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

    public string $soggetto;
    public string $tipoRicorrenza;
    public string|null $ricorrenza;
    public int|null $anno;
    public int $mese;
    public int $giorno;
    public string $calendario;
    public string|null $luogoNascita;
    public string|null $luogoMorte;
    public string|null $luogoSepoltura;
    public string|null $santuarioPrincipale;
    public string|null $luoghi;
    public array $ambitoDiInteresse;
    public string|null $note;
    public string|null $anniversario;
    public string|null $patronato;
    public int $yearDiff;

    public string $subject;
    public string $anniversaryType;
    public string|null $anniversaryName;
    public int|null $year;
    public int $month;
    public int $day;
    public string $calendar;
    public string|null $placeOfBirth;
    public string|null $placeOfDeath;
    public string|null $placeOfBurial;
    public string|null $mainShrine;
    public string|null $places;
    public array $areaOfInterest;
    public string|null $notes;
    public string|null $anniversary;
    public string|null $patronage;

    function __construct( array $rowData, string $locale ){
        $AnnivType                  = new AnnivType( $locale );
        $AreaInterest               = new AreaInterest( $locale );
        $LitCalendar                = new LitCalendar( $locale );

        $this->idx                  = $rowData["IDX"];
        $this->tag                  = $rowData["TAG"];

        $this->soggetto             = $rowData["SUBJECT"];
        $this->tipoRicorrenza       = $AnnivType->i18n( $rowData["ANNIVERSARY"] );
        $this->anno                 = $rowData["YEAR"];
        $this->mese                 = $rowData["MONTH"];
        $this->giorno               = $rowData["DAY"];
        $this->calendario           = $LitCalendar->i18n( $rowData["CALENDAR"] );
        $this->luogoNascita         = $rowData["PLACE_OF_BIRTH"];
        $this->luogoMorte           = $rowData["PLACE_OF_DEATH"];
        $this->luogoSepoltura       = $rowData["PLACE_OF_BURIAL"];
        $this->santuarioPrincipale  = $rowData["MAIN_SHRINE"];
        $this->luoghi               = $rowData["PLACES"];
        $this->ambitoDiInteresse    = $rowData["AREA"] ? $AreaInterest->i18n( explode( ",", $rowData["AREA"] ) ) : [];
        $this->note                 = $rowData["NOTES"];
        $this->patronato            = $rowData["PATRONAGE"];

        $this->subject             = $rowData["SUBJECT"];
        $this->anniversaryType     = $AnnivType->i18n( $rowData["ANNIVERSARY"] );
        $this->year                = $rowData["YEAR"];
        $this->month               = $rowData["MONTH"];
        $this->day                 = $rowData["DAY"];
        $this->calendar            = $LitCalendar->i18n( $rowData["CALENDAR"] );
        $this->placeOfBirth        = $rowData["PLACE_OF_BIRTH"];
        $this->placeOfDeath        = $rowData["PLACE_OF_DEATH"];
        $this->placeOfBurial       = $rowData["PLACE_OF_BURIAL"];
        $this->mainShrine          = $rowData["MAIN_SHRINE"];
        $this->places              = $rowData["PLACES"];
        $this->areaOfInterest      = $rowData["AREA"] ? $AreaInterest->i18n( explode( ",", $rowData["AREA"] ) ) : [];
        $this->notes               = $rowData["NOTES"];
        $this->patronage           = $rowData["PATRONAGE"];

    }

    public function setAnniversary( int $anniv ) {
        $this->anniversario = array_search( $anniv, self::ANNIVERSARY );
        $this->anniversary = array_search( $anniv, self::ANNIVERSARY );
    }

    public function setYearDiff( int $yearDiff ) {
        $this->yearDiff = $yearDiff;
    }

}
