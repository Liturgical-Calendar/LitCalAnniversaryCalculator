<?php

class AnnivType {
    const BIRTH = "birth";
    const DEATH = "death";
    const CANONIZATION = "canonization";
    const DOCTOR = "doctor";
    const DEDICATION = "dedication";
    const TRANSLATION = "translation";
    const OTHER = "other";
    private string $locale;
    private array $GTXT;

    public static array $values = [
        "birth", "death", "canonization", "doctor", "dedication", "translation", "other"
    ];

    public function __construct( string $locale ) {
        $this->locale = strtoupper( $locale );
        $this->GTXT = [
            self::BIRTH         => strtoupper( _( "birth" ) ),
            self::DEATH         => strtoupper( _( "death" ) ),
            self::CANONIZATION  => strtoupper( _( "canonization" ) ),
            self::DOCTOR        => strtoupper( _( "doctor" ) ),
            self::DEDICATION    => strtoupper( _( "dedication" ) ),
            /**translators: term "translation" refers to the transferral of the relics of a saint */
            self::TRANSLATION    => strtoupper( _( "translation" ) ),
            self::OTHER         => strtoupper( _( "other" ) )
        ];
    }

    public static function isValid( string $value ) {
        return in_array( $value, self::$values );
    }

    public function i18n( string $value ) : string {
        if( self::isValid( $value ) ) {
            return $this->GTXT[ $value ];
        }
        return $value;
    }

}
