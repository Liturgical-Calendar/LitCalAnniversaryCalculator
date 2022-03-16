<?php

include 'LitEvent.php';
define( "LITCALVERIFICATION", "for security purposes" );

class ANNIVERSARY_CALCULATOR {

    const ALLOWED_RETURN_TYPES               = [ "json", "xml", "html" ];
    const ALLOWED_ACCEPT_HEADERS             = [ "application/json", "application/xml", "text/html" ];
    const ALLOWED_CONTENT_TYPES              = [ "application/json", "application/x-www-form-urlencoded" ];
    const ALLOWED_REQUEST_METHODS            = [ "GET", "POST" ];

    const RECURRING = [
        "STAGNO",
        "PORCELLANA",
        "ARGENTO",
        "PERLA",
        "RUBINO",
        "ORO",
        "DIAMANTE",
        "FERRO",
        "PLATINO",
        "QUERCIA",
        "GRANITO",
        "CENTENARIO"
    ];

    private string $responseContentType;
    private string $acceptHeader        = "";
    private string $table;
    private array $parameterData        = [];
    private array $requestHeaders       = [];
    private mysqli $mysqli;
    private object $RESPONSE;

    function __construct() {
        $this->requestHeaders = getallheaders();
        $this->jsonEncodedRequestHeaders = json_encode( $this->requestHeaders );
        $this->acceptHeader = isset( $this->requestHeaders["Accept"] ) && in_array( $this->requestHeaders["Accept"], self::ALLOWED_ACCEPT_HEADERS ) ? ( string ) $this->requestHeaders["Accept"] : "";
        $this->RESPONSE = new stdClass();
        $this->RESPONSE->LitEvents = [];
        $this->RESPONSE->Messages = [ "Anniversary Calculator instantiated" ];
    }

    public function Init() {
        self::allowFromAnyOrigin();
        self::setAccessControlAllowMethods();
        self::validateRequestContentType();

        $this->initParameterData();
        $this->setReponseContentTypeHeader();
        $this->dbConnect();
        $this->dbWalk();
        $this->outputResults();
    }

    private static function allowFromAnyOrigin() {
        if ( isset( $_SERVER['HTTP_ORIGIN'] ) ) {
            header( "Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}" );
            header( 'Access-Control-Allow-Credentials: true' );
            header( 'Access-Control-Max-Age: 86400' );    // cache for 1 day
        }
    }

    private static function setAccessControlAllowMethods() {
        if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
            if ( isset( $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] ) )
                header( "Access-Control-Allow-Methods: GET, POST" );
            if ( isset( $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ) )
                header( "Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}" );
        }
    }

    private static function validateRequestContentType() {
        if( isset( $_SERVER['CONTENT_TYPE'] ) && $_SERVER['CONTENT_TYPE'] !== '' && !in_array( $_SERVER['CONTENT_TYPE'], self::ALLOWED_CONTENT_TYPES ) ){
            header( $_SERVER["SERVER_PROTOCOL"]." 415 Unsupported Media Type", true, 415 );
            die( '{"error":"You seem to be forming a strange kind of request? Allowed Content Types are '.implode( ' and ',self::ALLOWED_CONTENT_TYPES ).', but your Content Type was '.$_SERVER['CONTENT_TYPE'].'"}' );
        }
    }

    private function initParameterData() {
        if ( isset( $_SERVER['CONTENT_TYPE'] ) && $_SERVER['CONTENT_TYPE'] === 'application/json' ) {
            $json = file_get_contents( 'php://input' );
            $data = json_decode( $json,true );
            if( NULL === $json || "" === $json ){
                header( $_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400 );
                die( '{"error":"No JSON data received in the request: <' . $json . '>"' );
            } else if ( json_last_error() !== JSON_ERROR_NONE ) {
                header( $_SERVER["SERVER_PROTOCOL"]." 400 Bad Request", true, 400 );
                die( '{"error":"Malformed JSON data received in the request: <' . $json . '>, ' . json_last_error_msg() . '"}' );
            } else {
                $this->parameterData = $data;
            }
        } else {
            switch( strtoupper( $_SERVER["REQUEST_METHOD"] ) ) {
                case 'POST':
                    $this->parameterData = $_POST;
                    break;
                case 'GET':
                    $this->parameterData = $_GET;
                    break;
                default:
                    header( $_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed", true, 405 );
                    $errorMessage = '{"error":"You seem to be forming a strange kind of request? Allowed Request Methods are ';
                    $errorMessage .= implode( ' and ', self::ALLOWED_REQUEST_METHODS );
                    $errorMessage .= ', but your Request Method was ' . strtoupper( $_SERVER['REQUEST_METHOD'] ) . '"}';
                    die( $errorMessage );
            }
        }

        if( !isset( $this->parameterData["YEAR"] ) || $this->parameterData["YEAR"] === "" ) {
            die( '{"error":"Parametro YEAR non impostato o non valido"}' );
        }

        $this->responseContentType = ( isset( $this->parameterData["return"] ) && in_array( strtolower( $this->parameterData["return"] ), self::ALLOWED_RETURN_TYPES ) ) ? strtolower( $this->parameterData["return"] ) : ( $this->acceptHeader !== "" ? ( string ) self::ALLOWED_RETURN_TYPES[array_search( $this->requestHeaders["Accept"], self::ALLOWED_ACCEPT_HEADERS )] : ( string ) self::ALLOWED_RETURN_TYPES[0] );
        $this->RESPONSE->Messages[] = "parameter data initialized";
    }

    private function setReponseContentTypeHeader() {
        switch( $this->responseContentType ){
            case "xml":
              header( 'Content-Type: application/xml; charset=utf-8' );
              break;
            case "json":
              header( 'Content-Type: application/json; charset=utf-8' );
              break;
            case "html":
              header( 'Content-Type: text/html; charset=utf-8' );
              break;
            default:
              header( 'Content-Type: application/json; charset=utf-8' );
        }
        $this->RESPONSE->Messages[] = "Response Content-Type header set";
    }

    private function dbConnect() {

        $dbCredentials = "dbcredentials.php";
        //search for the database credentials file at least three levels up...
        if( file_exists( $dbCredentials ) ){
            include_once( $dbCredentials );
        } else if ( file_exists( "../{$dbCredentials}" ) ){
            include_once( "../{$dbCredentials}" );
        } else if ( file_exists( "../../{$dbCredentials}" ) ){
            include_once( "../../{$dbCredentials}" );
        }

        $mysqli = new mysqli( SERVER, DBUSER, DBPASS, DATABASE );

        if ( $mysqli->connect_errno ) {
            die( '{"error":"Failed to connect to the database: ' . $mysqli->connect_error . '"}' );
        }

        $mysqli->set_charset( "utf8" );
        $this->mysqli = $mysqli;
        $this->table = defined('TABLE') ? TABLE : 'anniversari';
        $this->RESPONSE->Messages[] = "Connected to Database";
    }

    private function isAnniversary( LitEvent $litEvent ) : bool {

        $yearDiff = $this->parameterData["YEAR"] - $litEvent->anno;
        $litEvent->setYearDiff( $yearDiff );

        foreach( LitEvent::ANNIVERSARY as $key => $value ) {

            if ( in_array( $key, self::RECURRING ) ) {

                if( $key === "CENTENARIO" ) {

                    if( $yearDiff % LitEvent::ANNIVERSARY["CENTENARIO"] === 0 ) {
                        $litEvent->setAnniversary( LitEvent::ANNIVERSARY["CENTENARIO"] );
                        return true;
                    }

                }

                $lastTwoDigits = substr((string)$yearDiff, -2);
                if( $key === array_search( (int)$lastTwoDigits, LitEvent::ANNIVERSARY ) ){
                    $litEvent->setAnniversary( LitEvent::ANNIVERSARY[$key] );
                    return true;
                }
            } else {
                $arraySearch = array_search( $yearDiff, LitEvent::ANNIVERSARY );
                if( $arraySearch !== false ) {
                    $litEvent->setAnniversary( $yearDiff );
                    return true;
                }

            }

        }

        return false;

    }

    private function dbWalk() {

        $result = $this->mysqli->query("SELECT * FROM {$this->table} ORDER BY MESE, GIORNO");
        while( $row = $result->fetch_assoc() ){
            $litEvent = new LitEvent( $row );
            if( $litEvent->anno !== null && $this->isAnniversary( $litEvent ) ) {
                $this->RESPONSE->LitEvents[] = $litEvent;
            }
        }
        $this->RESPONSE->Messages[] = "database rows calculated";
    }

    private function outputResults() {

        echo json_encode( $this->RESPONSE, JSON_UNESCAPED_UNICODE );

        if ( $this->mysqli && $this->mysqli->thread_id ) {
            $this->mysqli->close();
        }
        exit( 0 );

    }

}
