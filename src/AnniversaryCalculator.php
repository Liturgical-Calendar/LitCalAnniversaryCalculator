<?php

namespace LitCal;

use LitCal\AnniversaryCalculator\LitEvent;

class AnniversaryCalculator
{
    public const ALLOWED_RETURN_TYPES               = [ "json", "xml", "html" ];
    public const ALLOWED_ACCEPT_HEADERS             = [ "application/json", "application/xml", "text/html" ];
    public const ALLOWED_CONTENT_TYPES              = [ "application/json", "application/x-www-form-urlencoded" ];
    public const ALLOWED_REQUEST_METHODS            = [ "GET", "POST" ];
    public const ALLOWED_LOCALES                    = [ "en", "it" ]; //, "es", "fr", "de", "pt"

    public const RECURRING = [
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
        "CENTENARIO",
        "ALUMINUM",
        "PORCELAIN",
        "SILVER",
        "PEARL",
        "RUBY",
        "GOLD",
        "DIAMOND",
        "IRON",
        "PLATINUM",
        "OAK",
        "GRANITE",
        "CENTENARY"
    ];

    private string $responseContentType;
    private string $acceptHeader        = "";
    //private string $table;
    private array $parameterData        = [];
    private array $requestHeaders       = [];
    private object $RESPONSE;
    //private string|false $jsonEncodedRequestHeaders = "";

    public function __construct()
    {
        $this->requestHeaders = getallheaders();
        //$this->jsonEncodedRequestHeaders = json_encode( $this->requestHeaders );
        $this->acceptHeader = isset($this->requestHeaders["Accept"]) && in_array($this->requestHeaders["Accept"], self::ALLOWED_ACCEPT_HEADERS) ? (string) $this->requestHeaders["Accept"] : "";
        $this->RESPONSE = new \stdClass();
        $this->RESPONSE->LitEvents = [];
        $this->RESPONSE->Messages = [ "Anniversary Calculator instantiated" ];
    }

    public function init()
    {
        self::allowFromAnyOrigin();
        self::setAccessControlAllowMethods();
        self::validateRequestContentType();

        $this->initParameterData();
        $this->prepareL10N();
        $this->setReponseContentTypeHeader();
        $this->readData();
        $this->outputResults();
    }

    private static function allowFromAnyOrigin()
    {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');    // cache for 1 day
        }
    }

    private static function setAccessControlAllowMethods()
    {
        if (isset($_SERVER['REQUEST_METHOD'])) {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                header("Access-Control-Allow-Methods: GET, POST");
            }
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }
        }
    }

    private static function validateRequestContentType()
    {
        if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] !== '' && !in_array($_SERVER['CONTENT_TYPE'], self::ALLOWED_CONTENT_TYPES)) {
            header($_SERVER["SERVER_PROTOCOL"] . " 415 Unsupported Media Type", true, 415);
            die('{"error":"You seem to be forming a strange kind of request? Allowed Content Types are ' . implode(' and ', self::ALLOWED_CONTENT_TYPES) . ', but your Content Type was ' . $_SERVER['CONTENT_TYPE'] . '"}');
        }
    }

    private function initParameterData()
    {
        if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            if (null === $json || "" === $json) {
                header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                die('{"error":"No JSON data received in the request: <' . $json . '>"');
            } else if (json_last_error() !== JSON_ERROR_NONE) {
                header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                die('{"error":"Malformed JSON data received in the request: <' . $json . '>, ' . json_last_error_msg() . '"}');
            } else {
                $this->parameterData = $data;
            }
        } else {
            switch (strtoupper($_SERVER["REQUEST_METHOD"])) {
                case 'POST':
                    $this->parameterData = $_POST;
                    break;
                case 'GET':
                    $this->parameterData = $_GET;
                    break;
                default:
                    header($_SERVER["SERVER_PROTOCOL"] . " 405 Method Not Allowed", true, 405);
                    $errorMessage = '{"error":"You seem to be forming a strange kind of request? Allowed Request Methods are ';
                    $errorMessage .= implode(' and ', self::ALLOWED_REQUEST_METHODS);
                    $errorMessage .= ', but your Request Method was ' . strtoupper($_SERVER['REQUEST_METHOD']) . '"}';
                    die($errorMessage);
            }
        }

        if (!isset($this->parameterData["YEAR"]) || $this->parameterData["YEAR"] === "") {
            $this->parameterData["YEAR"] = (int)date("Y");
            //die( '{"error":"Parametro YEAR non impostato o non valido"}' );
        }

        if (isset($this->parameterData["LOCALE"]) && in_array(strtolower($this->parameterData["LOCALE"]), self::ALLOWED_LOCALES)) {
            $this->parameterData["LOCALE"] = strtolower($this->parameterData["LOCALE"]);
        } else {
            $this->parameterData["LOCALE"] = "en";
        }

        $this->responseContentType = ( isset($this->parameterData["return"]) && in_array(strtolower($this->parameterData["return"]), self::ALLOWED_RETURN_TYPES) ) ? strtolower($this->parameterData["return"]) : ( $this->acceptHeader !== "" ? (string) self::ALLOWED_RETURN_TYPES[array_search($this->requestHeaders["Accept"], self::ALLOWED_ACCEPT_HEADERS)] : (string) self::ALLOWED_RETURN_TYPES[0] );
        $this->RESPONSE->Messages[] = "parameter data initialized";
    }

    private function prepareL10N(): void
    {
        $localeArray = [
            $this->parameterData["LOCALE"] . '_' . strtoupper($this->parameterData["LOCALE"]) . '.utf8',
            $this->parameterData["LOCALE"] . '_' . strtoupper($this->parameterData["LOCALE"]) . '.UTF-8',
            $this->parameterData["LOCALE"] . '_' . strtoupper($this->parameterData["LOCALE"]),
            $this->parameterData["LOCALE"]
        ];
        setlocale(LC_ALL, $localeArray);
        bindtextdomain("litcal", "i18n");
        textdomain("litcal");
    }

    private function setReponseContentTypeHeader()
    {
        switch ($this->responseContentType) {
            case "xml":
                header('Content-Type: application/xml; charset=utf-8');
                break;
            case "json":
                header('Content-Type: application/json; charset=utf-8');
                break;
            case "html":
                header('Content-Type: text/html; charset=utf-8');
                break;
            default:
                header('Content-Type: application/json; charset=utf-8');
        }
        $this->RESPONSE->Messages[] = "Response Content-Type header set";
    }

    private function readData()
    {
        if (file_exists("./data/LITURGY__anniversari.json")) {
            if (file_exists("./data/i18n/{$this->parameterData["LOCALE"]}.json")) {
                $lclData = json_decode(file_get_contents("./data/i18n/{$this->parameterData["LOCALE"]}.json"));
                $results = json_decode(file_get_contents("./data/LITURGY__anniversari.json"));
                $this->RESPONSE->Messages[] = "localized data events loaded: " . count(get_object_vars($lclData));
                $this->RESPONSE->Messages[] = "base data events loaded: " . count($results);
                foreach ($lclData as $label => $lclRow) {
                    list( $TAG, $IDX ) = explode("_", $label);
                    foreach ($results as $idx => $obj) {
                        if ($obj->TAG === $TAG && $obj->IDX === intval($IDX)) {
                            $litEvent = new LitEvent(array_merge((array) $results[$idx], (array) $lclRow), $this->parameterData["LOCALE"]);
                            if ($litEvent->year !== null && $this->isAnniversary($litEvent)) {
                                $this->RESPONSE->LitEvents[] = $litEvent;
                            }
                        }
                    }

                    $props = [
                        "month" => 2,
                        "day"   => 1
                    ];
                    usort($this->RESPONSE->LitEvents, function ($a, $b) use ($props) {
                        foreach ($props as $key => $val) {
                            if ($a->$key == $b->$key) {
                                continue;
                            }
                            return $a->$key > $b->$key ? $val : -($val);
                        }
                        return 0;
                    });
                }
                $this->RESPONSE->Messages[] = count($this->RESPONSE->LitEvents) . " data rows calculated";
            } else {
                $this->RESPONSE->Messages[] = "missing file ./data/i18n/{$this->parameterData["LOCALE"]}.json";
            }
        } else {
            $this->RESPONSE->Messages[] = "missing file ./data/LITURGY__anniversari.json";
        }
    }


    private function isAnniversary(LitEvent $litEvent): bool
    {

        $yearDiff = $this->parameterData["YEAR"] - $litEvent->anno;
        $litEvent->setYearDiff($yearDiff);

        foreach (LitEvent::ANNIVERSARY as $key => $value) {
            if (in_array($key, self::RECURRING)) {
                if ($key === "CENTENARY") {
                    if ($yearDiff % LitEvent::ANNIVERSARY["CENTENARY"] === 0) {
                        $litEvent->setAnniversary(LitEvent::ANNIVERSARY["CENTENARY"]);
                        return true;
                    }
                }

                $lastTwoDigits = substr((string)$yearDiff, -2);
                if ($key === array_search((int)$lastTwoDigits, LitEvent::ANNIVERSARY)) {
                    $litEvent->setAnniversary(LitEvent::ANNIVERSARY[$key]);
                    return true;
                }
            } else {
                $arraySearch = array_search($yearDiff, LitEvent::ANNIVERSARY);
                if ($arraySearch !== false) {
                    $litEvent->setAnniversary($yearDiff);
                    return true;
                }
            }
        }

        return false;
    }

    private function outputResults()
    {

        echo json_encode($this->RESPONSE, JSON_UNESCAPED_UNICODE);
        exit(0);
    }
}
