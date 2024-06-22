<?php

namespace LitCal;

use LitCal\AnniversaryCalculator\LitEvent;

class AnniversaryCalculator
{
    public const ALLOWED_RETURN_TYPES               = [ "json", "yaml", "xml", "html" ];
    public const ALLOWED_ACCEPT_HEADERS             = [ "application/json", "application/yaml", "application/xml", "text/html" ];
    public const ALLOWED_CONTENT_TYPES              = [ "application/json", "application/yaml", "application/x-www-form-urlencoded" ];
    public const ALLOWED_REQUEST_METHODS            = [ "GET", "POST" ];
    public const ALLOWED_LOCALES                    = [ "en", "it" ]; //, "es", "fr", "de", "pt"

    public const RECURRING = [
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
            $rawJson = file_get_contents('php://input');
            if (null === $rawJson || "" === $rawJson) {
                header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                $response = new \stdClass();
                $response->error = _("No JSON data received in the request");
                die(json_encode($response));
            }
            $data = json_decode($rawJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
                $response = new \stdClass();
                $response->error = sprintf(
                    _('Malformed JSON data received in the request: %s'),
                    json_last_error_msg()
                );
                die(json_encode($response));
            } else {
                $this->parameterData = $data;
            }
        } elseif (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/yaml') {
            $rawYaml = file_get_contents('php://input');
            if ("" === $rawYaml) {
                header($_SERVER[ "SERVER_PROTOCOL" ] . " 400 Bad Request", true, 400);
                $response = new \stdClass();
                $response->error = _("No YAML data received in the request");
                die(json_encode($response));
            }

            set_error_handler(array('self', 'warningHandler'), E_WARNING);
            try {
                $data = yaml_parse($rawYaml);
                if (false === $data) {
                    header($_SERVER[ "SERVER_PROTOCOL" ] . " 400 Bad Request", true, 400);
                    $response = new \stdClass();
                    $response->error = _("Malformed YAML data received in the request");
                    die(json_encode($response));
                } else {
                    $this->parameterData = $data;
                }
            } catch (\Exception $e) {
                header($_SERVER[ "SERVER_PROTOCOL" ] . " 400 Bad Request", true, 400);
                $response = new \stdClass();
                $response->error = sprintf(
                    _("Malformed YAML data received in the request: %s"),
                    $e->getMessage()
                );
                die(json_encode($response));
            }
        } else {
            switch (strtoupper($_SERVER["REQUEST_METHOD"])) {
                case 'POST':
                    $_POST = array_change_key_case($_POST, CASE_UPPER);
                    $this->parameterData = $_POST;
                    break;
                case 'GET':
                    $_GET = array_change_key_case($_GET, CASE_UPPER);
                    $this->parameterData = $_GET;
                    break;
                default:
                    header($_SERVER["SERVER_PROTOCOL"] . " 405 Method Not Allowed", true, 405);
                    $response = new \stdClass();
                    $response->status = "error";
                    $response->message = sprintf(
                        _('Allowed request methods are: %1$s; but request method was \'%2$s\''),
                        implode(', ', self::ALLOWED_REQUEST_METHODS),
                        strtoupper($_SERVER['REQUEST_METHOD'])
                    );
                    die(json_encode($response));
            }
        }

        if (!isset($this->parameterData["YEAR"]) || $this->parameterData["YEAR"] === "") {
            $this->parameterData["YEAR"] = (int)date("Y");
        }
        $this->RESPONSE->Messages[] = sprintf(
            _('Year set to %d'),
            $this->parameterData["YEAR"]
        );

        if (isset($this->parameterData["LOCALE"]) && $this->parameterData["LOCALE"] !== '') {
            $this->parameterData["LOCALE"] = \Locale::canonicalize($this->parameterData["LOCALE"]);
            $this->parameterData["BASE_LOCALE"] = \Locale::getPrimaryLanguage($this->parameterData["LOCALE"]);
            if (false === in_array($this->parameterData["BASE_LOCALE"], self::ALLOWED_LOCALES)) {
                $this->parameterData["LOCALE"] = "en_US";
                $this->parameterData["BASE_LOCALE"] = \Locale::getPrimaryLanguage($this->parameterData["LOCALE"]);
                $this->RESPONSE->Messages[] = sprintf(
                    _('Allowed base locales are: \'%1$s\'; but base locale requested was \'%2$s\''),
                    implode(', ', self::ALLOWED_LOCALES),
                    $this->parameterData["BASE_LOCALE"]
                );
            }
        } else {
            $this->parameterData["LOCALE"] = "en_US";
            $this->parameterData["BASE_LOCALE"] = \Locale::getPrimaryLanguage($this->parameterData["LOCALE"]);
        }
        $this->RESPONSE->Messages[] = sprintf(
            _('Locale set to \'%1$s\', base locale set to \'%2$s\''),
            $this->parameterData["LOCALE"],
            $this->parameterData["BASE_LOCALE"]
        );

        $this->responseContentType = (
                isset($this->parameterData["RETURN"])
                && in_array(strtolower($this->parameterData["RETURN"]), self::ALLOWED_RETURN_TYPES)
            )
            ? strtolower($this->parameterData["RETURN"])
            : (
                $this->acceptHeader !== ""
                ? (string) self::ALLOWED_RETURN_TYPES[array_search($this->requestHeaders["Accept"], self::ALLOWED_ACCEPT_HEADERS)]
                : (string) self::ALLOWED_RETURN_TYPES[0]
            );
        $this->RESPONSE->Messages[] = sprintf(
            _('Return parameter set to \'%1$s\', response content type set to \'%2$s\''),
            $this->parameterData["RETURN"],
            $this->responseContentType
        );
        $this->RESPONSE->Messages[] = _("parameter data initialized");
    }

    private static function warningHandler($errno, $errstr)
    {
        throw new \Exception($errstr, $errno);
    }

    private function prepareL10N(): void
    {
        $localeArray = [
            $this->parameterData["LOCALE"] . '.utf8',
            $this->parameterData["LOCALE"] . '.UTF-8',
            $this->parameterData["BASE_LOCALE"] . '_' . strtoupper($this->parameterData["BASE_LOCALE"]) . '.utf8',
            $this->parameterData["BASE_LOCALE"] . '_' . strtoupper($this->parameterData["BASE_LOCALE"]) . '.UTF-8',
            $this->parameterData["LOCALE"],
            $this->parameterData["BASE_LOCALE"] . '_' . strtoupper($this->parameterData["BASE_LOCALE"])
        ];
        setlocale(LC_ALL, $localeArray);
        bindtextdomain("litcal", "i18n");
        textdomain("litcal");
    }

    private function setReponseContentTypeHeader()
    {
        $header = '';
        switch ($this->responseContentType) {
            case "xml":
                $header = 'Content-Type: application/xml; charset=utf-8';
                break;
            case "json":
                $header = 'Content-Type: application/json; charset=utf-8';
                break;
            case "yaml":
                $header = 'Content-Type: application/yaml; charset=utf-8';
                break;
            case "html":
                $header = 'Content-Type: text/html; charset=utf-8';
                break;
            default:
                $header = 'Content-Type: application/json; charset=utf-8';
        }
        header($header);
        $this->RESPONSE->Messages[] = sprintf(
            _("Response Content-Type header set to '%s'"),
            $header
        );
    }

    private function readData()
    {
        $dataFile = "data/LITURGY__anniversari.json";
        $translationFile = "data/i18n/{$this->parameterData["BASE_LOCALE"]}.json";
        if (file_exists($dataFile)) {
            if (file_exists($translationFile)) {
                $lclData = json_decode(file_get_contents($translationFile));
                $results = json_decode(file_get_contents($dataFile));
                $this->RESPONSE->Messages[] = sprintf(
                    _("%d localized data events loaded from translation file %s"),
                    count(get_object_vars($lclData)),
                    $translationFile
                );
                $this->RESPONSE->Messages[] = sprintf(
                    _("%d events loaded from data file %s"),
                    count($results),
                    $dataFile
                );
                foreach ($lclData as $label => $lclRow) {
                    list( $TAG, $IDX ) = explode("_", $label);
                    foreach ($results as $idx => $obj) {
                        if ($obj->TAG === $TAG && $obj->IDX === intval($IDX)) {
                            $litEvent = new LitEvent(array_merge((array) $results[$idx], (array) $lclRow));
                            if ($litEvent->year !== null && $this->isAnniversary($litEvent)) {
                                $this->RESPONSE->LitEvents[] = $litEvent;
                            }
                        }
                    }
                    //TODO: we currently sort by liturgical memorial day / month, because that's the data we started with,
                    //      however it would be more useful, once the data is defined, to sort by event day / month
                    $props = [
                        "memorialMonth" => 2,
                        "memorialDay"   => 1
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
                $this->RESPONSE->Messages[] = sprintf(
                    _("%d data rows calculated"),
                    count($this->RESPONSE->LitEvents)
                );
            } else {
                $this->RESPONSE->Messages[] = sprintf(
                    _("missing translation file: %s"),
                    $translationFile
                );
            }
        } else {
            $this->RESPONSE->Messages[] = sprintf(
                _("missing data file: %s"),
                $dataFile
            );
        }
    }


    private function isAnniversary(LitEvent $litEvent): bool
    {

        $yearDiff = $this->parameterData["YEAR"] - $litEvent->year;
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
        switch ($this->responseContentType) {
            case 'yaml':
                $responseObj = json_decode(json_encode($this->RESPONSE), true);
                echo yaml_emit($responseObj, YAML_UTF8_ENCODING);
                break;
            case 'xml':
                //TODO: NOT YET SUPPORTED
                break;
            case 'html':
                //TODO: NOT YET SUPPORTED
                break;
            case 'json':
            default:
                echo json_encode($this->RESPONSE, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                break;
        }
        exit(0);
    }
}
