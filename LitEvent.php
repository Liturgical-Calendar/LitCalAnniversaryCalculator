<?php

class LitEvent {

    const MONTHS = [
        "null_value",
        "GENNAIO",
        "FEBBRAIO",
        "MARZO",
        "APRILE",
        "MAGGIO",
        "GIUGNO",
        "LUGLIO",
        "AGOSTO",
        "SETTEMBRE",
        "OTTOBRE",
        "NOVEMBRE",
        "DICEMBRE"
    ];

    const ANNIVERSARY = [
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

    public int $idx;
    public string $soggetto;
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
    public array $ambito;
    public string|null $note;
    public string|null $anniversario;
    public string|null $patronato;
    public int $yearDiff;

    function __construct( array $rowData ){
        $this->idx                  = $rowData["IDX"];
        $this->soggetto             = $rowData["SOGGETTO"];
        $this->ricorrenza           = $rowData["RICORRENZA"];
        $this->anno                 = $rowData["ANNO"];
        $this->mese                 = array_search( $rowData["MESE"], self::MONTHS );
        $this->giorno               = $rowData["GIORNO"];
        $this->calendario           = $rowData["CALENDARIO"];
        $this->luogoNascita         = $rowData["LUOGO_NASCITA"];
        $this->luogoMorte           = $rowData["LUOGO_MORTE"];
        $this->luogoSepoltura       = $rowData["LUOGO_SEPOLTURA"];
        $this->santuarioPrincipale  = $rowData["SANTUARIO_PRINCIPALE"];
        $this->luoghi               = $rowData["LUOGHI"];
        $this->ambito               = $rowData["AMBITO"] ? explode( ",", $rowData["AMBITO"] ) : [];
        $this->note                 = $rowData["NOTE"];
        $this->patronato            = $rowData["PATRONO"];
    }

    public function setAnniversary( int $anniv ) {
        $this->anniversario = array_search( $anniv, self::ANNIVERSARY );
    }

    public function setYearDiff( int $yearDiff ) {
        $this->yearDiff = $yearDiff;
    }

}
