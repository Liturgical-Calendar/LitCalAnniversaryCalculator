<?php

class ANNIVERSARY_CALCULATOR {

    const ALLOWED_RETURN_TYPES               = [ "json", "xml", "html" ];
    const ALLOWED_ACCEPT_HEADERS             = [ "application/json", "application/xml", "text/html" ];
    const ALLOWED_CONTENT_TYPES              = [ "application/json", "application/x-www-form-urlencoded" ];
    const ALLOWED_REQUEST_METHODS            = [ "GET", "POST" ];

    private array $parameterData;
    private mysqli $mysqli;

    function __construct() {

    }

    public function Init() {
        self::allowFromAnyOrigin();
        self::setAccessControlAllowMethods();
        self::validateRequestContentType();

        $this->initParameterData();
        $this->dbConnect();
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
        if( isset( $_SERVER['CONTENT_TYPE'] ) && !in_array( $_SERVER['CONTENT_TYPE'], self::ALLOWED_CONTENT_TYPES ) ){
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

        $mysqli = new mysqli( SERVER,DBUSER,DBPASS,DATABASE );

        if ( $mysqli->connect_errno ) {
            //$this->addErrorMessage( "Failed to connect to MySQL: ( " . $mysqli->connect_errno . " ) " . $mysqli->connect_error );
            //$this->outputResult();
        }
        $mysqli->set_charset( "utf8" );
        $this->mysqli = $mysqli;
    }

}



?>