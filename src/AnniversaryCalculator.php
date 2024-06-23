<?php

namespace LitCal;

use LitCal\AnniversaryCalculator\LitEvent;
use LitCal\AnniversaryCalculator\Enums\StatusCode;

class AnniversaryCalculator
{
    public const ALLOWED_RETURN_TYPES               = [ "json", "yaml", "xml", "html" ];
    public const ALLOWED_ACCEPT_HEADERS             = [ "application/json", "application/yaml", "application/xml", "text/html" ];
    public const ALLOWED_REQUEST_CONTENT_TYPES      = [ "application/json", "application/yaml", "application/x-www-form-urlencoded" ];
    public const ALLOWED_REQUEST_METHODS            = [ "GET", "POST", "OPTIONS" ];
    public const ALLOWED_LOCALES                    = [ "en", "it", "es", "fr", "de", "pt" ];

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

    private static ?string $responseContentType = null;
    private static ?string $acceptHeader        = null;
    private array $parameterData                = [];
    private array $requestHeaders               = [];
    private object $RESPONSE;

    public function __construct()
    {
        $this->requestHeaders = getallheaders();
        self::$acceptHeader = isset($this->requestHeaders["Accept"]) && in_array($this->requestHeaders["Accept"], self::ALLOWED_ACCEPT_HEADERS)
            ? (string) $this->requestHeaders["Accept"]
            : "";
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
        self::setReponseContentTypeHeader();
        $this->readData();
        $this->produceResponse();
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
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            }
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }
        }
    }

    private static function validateRequestContentType()
    {
        if (
            isset($_SERVER['CONTENT_TYPE'])
            && $_SERVER['CONTENT_TYPE'] !== ''
            && !in_array($_SERVER['CONTENT_TYPE'], self::ALLOWED_REQUEST_CONTENT_TYPES)
        ) {
            $message = "Allowed Content Types are: " . implode(', ', self::ALLOWED_REQUEST_CONTENT_TYPES) . "; but the Content Type of the request was " . $_SERVER['CONTENT_TYPE'];
            self::produceErrorResponse(StatusCode::UNSUPPORTED_MEDIA_TYPE, $message);
        }
    }

    private function initParameterData()
    {
        if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
            $rawJson = file_get_contents('php://input');
            if (false === $rawJson || "" === $rawJson) {
                self::produceErrorResponse(StatusCode::BAD_REQUEST, "No JSON data received in the request");
            }
            $data = json_decode($rawJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $message = 'Malformed JSON data received in the request: ' . json_last_error_msg();
                self::produceErrorResponse(StatusCode::BAD_REQUEST, $message);
            } else {
                $this->parameterData = $data;
            }
        } elseif (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/yaml') {
            $rawYaml = file_get_contents('php://input');
            if (false === $rawYaml || "" === $rawYaml) {
                self::produceErrorResponse(StatusCode::BAD_REQUEST, "No YAML data received in the request");
            }

            set_error_handler(array('self', 'warningHandler'), E_WARNING);
            try {
                $data = yaml_parse($rawYaml);
                if (false === $data) {
                    self::produceErrorResponse(StatusCode::BAD_REQUEST, "Malformed YAML data received in the request");
                } else {
                    $this->parameterData = $data;
                }
            } catch (\Exception $e) {
                $message = "Malformed YAML data received in the request: " . $e->getMessage();
                self::produceErrorResponse(StatusCode::BAD_REQUEST, $message);
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
                    $message = sprintf(
                        'Allowed request methods are: %1$s; but request method was \'%2$s\'',
                        implode(', ', self::ALLOWED_REQUEST_METHODS),
                        $_SERVER['REQUEST_METHOD']
                    );
                    self::produceErrorResponse(StatusCode::METHOD_NOT_ALLOWED, $message);
            }
        }

        self::$responseContentType = (
            isset($this->parameterData["RETURN"])
            && in_array(strtolower($this->parameterData["RETURN"]), self::ALLOWED_RETURN_TYPES)
        )
            ? strtolower($this->parameterData["RETURN"])
            : (
                self::$acceptHeader !== null
                    ? (string) self::ALLOWED_RETURN_TYPES[array_search(self::$acceptHeader, self::ALLOWED_ACCEPT_HEADERS)]
                    : (string) self::ALLOWED_RETURN_TYPES[0]
            );
        $this->RESPONSE->Messages[] = sprintf(
            'Return parameter set to \'%1$s\', response content type set to \'%2$s\'',
            $this->parameterData["RETURN"],
            self::$responseContentType
        );

        if (!isset($this->parameterData["YEAR"]) || $this->parameterData["YEAR"] === "") {
            $this->parameterData["YEAR"] = (int)date("Y");
        }
        $this->RESPONSE->Messages[] = sprintf(
            'Year set to %d',
            $this->parameterData["YEAR"]
        );

        if (isset($this->parameterData["LOCALE"]) && $this->parameterData["LOCALE"] !== '') {
            $this->parameterData["LOCALE"] = \Locale::canonicalize($this->parameterData["LOCALE"]);
            $this->parameterData["BASE_LOCALE"] = \Locale::getPrimaryLanguage($this->parameterData["LOCALE"]);
            if (false === in_array($this->parameterData["BASE_LOCALE"], self::ALLOWED_LOCALES)) {
                $this->parameterData["LOCALE"] = "en_US";
                $this->parameterData["BASE_LOCALE"] = \Locale::getPrimaryLanguage($this->parameterData["LOCALE"]);
                $this->RESPONSE->Messages[] = sprintf(
                    'Allowed base locales are: \'%1$s\'; but base locale requested was \'%2$s\'',
                    implode(', ', self::ALLOWED_LOCALES),
                    $this->parameterData["BASE_LOCALE"]
                );
            }
        } else {
            $this->parameterData["LOCALE"] = "en_US";
            $this->parameterData["BASE_LOCALE"] = \Locale::getPrimaryLanguage($this->parameterData["LOCALE"]);
        }
        $this->RESPONSE->Messages[] = sprintf(
            'Locale set to \'%1$s\', base locale set to \'%2$s\'',
            $this->parameterData["LOCALE"],
            $this->parameterData["BASE_LOCALE"]
        );
        $this->RESPONSE->Messages[] = "parameter data initialized";
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
            $this->parameterData["LOCALE"],
            $this->parameterData["BASE_LOCALE"] . '_' . strtoupper($this->parameterData["BASE_LOCALE"]) . '.utf8',
            $this->parameterData["BASE_LOCALE"] . '_' . strtoupper($this->parameterData["BASE_LOCALE"]) . '.UTF-8',
            $this->parameterData["BASE_LOCALE"] . '_' . strtoupper($this->parameterData["BASE_LOCALE"]),
            $this->parameterData["BASE_LOCALE"] . '.utf8',
            $this->parameterData["BASE_LOCALE"] . '.UTF-8',
            $this->parameterData["BASE_LOCALE"]
        ];
        $locale = setlocale(LC_ALL, $localeArray);
        $textdomainpath = bindtextdomain("litcal", "i18n");
        $textdomain = textdomain("litcal");
        $this->RESPONSE->Messages[] = sprintf(
            'PHP setlocale set to locale %1$s, text domain path set to %2$s, text domain set to %3$s',
            $locale ? $locale : 'false',
            $textdomainpath,
            $textdomain
        );
    }

    private static function setReponseContentTypeHeader()
    {
        $header = null;
        switch (self::$responseContentType) {
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
                    /**translators: 1: count, 2: filename */
                    _('%1$d localized data events loaded from translation file %2$s'),
                    count(get_object_vars($lclData)),
                    $translationFile
                );
                $this->RESPONSE->Messages[] = sprintf(
                    /**translators: 1: count, 2: filename */
                    _('%1$d events loaded from data file %2$s'),
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
                    /**translators: count */
                    _("%d data rows calculated"),
                    count($this->RESPONSE->LitEvents)
                );
            } else {
                $this->RESPONSE->Messages[] = sprintf(
                    /**translators: filename */
                    _("missing translation file: %s"),
                    $translationFile
                );
            }
        } else {
            $this->RESPONSE->Messages[] = sprintf(
                /**translators: filename */
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

    private static function produceErrorResponse(int $statusCode, string $description): void
    {
        // if $responseContentType is null, we probably haven't set the response Content-Type header yet
        if (null === self::$responseContentType) {
            // so let's attempt at doing so the same way initParameterData handles it
            if (null !== self::$acceptHeader && in_array(self::$acceptHeader, self::ALLOWED_ACCEPT_HEADERS)) {
                self::$responseContentType = (string) self::ALLOWED_RETURN_TYPES[array_search(self::$acceptHeader, self::ALLOWED_ACCEPT_HEADERS)];
            } else {
                self::$responseContentType = (string) self::ALLOWED_RETURN_TYPES[0];
            }
            self::setReponseContentTypeHeader();
        }
        header($_SERVER[ "SERVER_PROTOCOL" ] . StatusCode::toString($statusCode), true, $statusCode);
        $message = new \stdClass();
        $message->status = "ERROR";
        $message->description = $description;
        $response = json_encode($message);
        switch (self::$responseContentType) {
            case 'yaml':
                $responseObj = json_decode($response, true);
                echo yaml_emit($responseObj, YAML_UTF8_ENCODING);
                break;
                break;
            case 'xml':
            case 'html':
                // do not emit anything, the header should be enough
                break;
            case 'json':
            default:
                echo $response;
        }
        die();
    }

    private function produceResponse()
    {
        switch (self::$responseContentType) {
            case 'yaml':
                $responseObj = json_decode(json_encode($this->RESPONSE), true);
                echo yaml_emit($responseObj, YAML_UTF8_ENCODING);
                break;
            case 'xml':
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
