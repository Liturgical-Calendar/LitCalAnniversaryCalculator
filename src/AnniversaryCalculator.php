<?php

namespace LiturgicalCalendar;

use LiturgicalCalendar\AnniversaryCalculator\AnniversaryEvent;
use LiturgicalCalendar\AnniversaryCalculator\Enums\StatusCode;

class AnniversaryCalculator
{
    public const ALLOWED_RETURN_TYPES               = [ "json", "yaml", "xml", "html" ];
    public const ALLOWED_ACCEPT_HEADERS             = [ "application/json", "application/yaml", "application/xml", "text/html" ];
    public const ALLOWED_REQUEST_CONTENT_TYPES      = [ "application/json", "application/yaml", "application/x-www-form-urlencoded" ];
    public const ALLOWED_REQUEST_METHODS            = [ "GET", "POST", "OPTIONS" ];
    public const ALLOWED_LOCALES                    = [ "en", "it", "fr", "es", "de", "pt", "nl" ];

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
        $this->RESPONSE->anniversary_events = [];
        $this->RESPONSE->messages = [ "Anniversary Calculator instantiated" ];
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
        if (isset($this->parameterData["RETURN"])) {
            $message = 'Return parameter set to \'%1$s\', response content type set to \'%2$s\'';
            $this->RESPONSE->messages[] = sprintf(
                $message,
                $this->parameterData["RETURN"],
                self::$responseContentType
            );
        } else {
            $message = 'No return parameter received in the request, response content type set to \'%1$s\'';
            $this->RESPONSE->messages[] = sprintf(
                $message,
                self::$responseContentType
            );
        }

        if (!isset($this->parameterData["YEAR"]) || $this->parameterData["YEAR"] === "") {
            $this->parameterData["YEAR"] = (int)date("Y");
        }
        $this->RESPONSE->messages[] = sprintf(
            'Year set to %d',
            $this->parameterData["YEAR"]
        );

        if (isset($this->parameterData["LOCALE"]) && $this->parameterData["LOCALE"] !== '') {
            $this->parameterData["LOCALE"] = \Locale::canonicalize($this->parameterData["LOCALE"]);
            $this->parameterData["BASE_LOCALE"] = \Locale::getPrimaryLanguage($this->parameterData["LOCALE"]);
            if (false === in_array($this->parameterData["BASE_LOCALE"], self::ALLOWED_LOCALES)) {
                $this->RESPONSE->messages[] = sprintf(
                    'Allowed base locales are: \'%1$s\'; but base locale requested was \'%2$s\'',
                    implode(', ', self::ALLOWED_LOCALES),
                    $this->parameterData["BASE_LOCALE"]
                );
                $this->parameterData["LOCALE"] = "en_US";
                $this->parameterData["BASE_LOCALE"] = \Locale::getPrimaryLanguage($this->parameterData["LOCALE"]);
            }
        } else {
            $this->parameterData["LOCALE"] = "en_US";
            $this->parameterData["BASE_LOCALE"] = \Locale::getPrimaryLanguage($this->parameterData["LOCALE"]);
        }
        $this->RESPONSE->messages[] = sprintf(
            'Locale set to \'%1$s\', base locale set to \'%2$s\'',
            $this->parameterData["LOCALE"],
            $this->parameterData["BASE_LOCALE"]
        );
        $this->RESPONSE->messages[] = "parameter data initialized";
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
        $this->RESPONSE->messages[] = sprintf(
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

    private function loadSourceData(string $sourceDataFile): object
    {
        if (false === file_exists($sourceDataFile)) {
            $message = sprintf(
                /**translators: filename */
                _("Data file %s is missing."),
                $sourceDataFile
            );
            self::produceErrorResponse(StatusCode::SERVICE_UNAVAILABLE, $message);
        }

        $rawSourceData = file_get_contents($sourceDataFile);
        $sourceData = json_decode($rawSourceData);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $message = sprintf(
                /**translators: 1. filename, 2. error messag */
                _('The following error occurred while decoding the base data file %1$s: %2$s.'),
                $sourceDataFile,
                json_last_error_msg()
            );
            self::produceErrorResponse(StatusCode::SERVICE_UNAVAILABLE, $message);
        }

        // if all went well
        $this->RESPONSE->messages[] = sprintf(
            /**translators: 1. count, 2. filename */
            _('%1$d events were loaded from the base data file %2$s.'),
            count($sourceData->anniversary_events),
            $sourceDataFile
        );
        return $sourceData;
    }

    private function loadTranslationData(string $translationFile): object
    {
        if (false === file_exists($translationFile)) {
            $message = sprintf(
                /**translators: filename */
                _("Translation file %s is missing."),
                $translationFile
            );
            self::produceErrorResponse(StatusCode::SERVICE_UNAVAILABLE, $message);
        }

        $rawTranslationData = file_get_contents($translationFile);
        $translationData = json_decode($rawTranslationData);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $message = sprintf(
                /**translators: 1. filename, 2. error message */
                _('The following error occurred decoding the translation file %1$s: %2$s.'),
                $translationFile,
                json_last_error_msg()
            );
            self::produceErrorResponse(StatusCode::SERVICE_UNAVAILABLE, $message);
        }

        // if all went well
        $this->RESPONSE->messages[] = sprintf(
            /**translators: 1. count, 2. filename */
            _('%1$d localized data events were loaded from the translation file %2$s.'),
            count(get_object_vars($translationData)),
            $translationFile
        );
        return $translationData;
    }

    private function loadEnglishTranslationData(string $englishTranslationFile): object
    {
        if (false === file_exists($englishTranslationFile)) {
            $message = sprintf(
                /**translators: 1. filename, 2. language */
                _('The English translation file %1$s, which is needed as a backup for any possible missing strings in the translation for %2$s, is missing.'),
                $englishTranslationFile,
                \Locale::getDisplayLanguage($this->parameterData["BASE_LOCALE"], $this->parameterData["BASE_LOCALE"])
            );
            self::produceErrorResponse(StatusCode::SERVICE_UNAVAILABLE, $message);
        }
        $rawDataEnglish = file_get_contents($englishTranslationFile);
        $translationDataEnglish = json_decode($rawDataEnglish);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $message = sprintf(
                _('The following error was produced while decoding data from the English tranlsation file %1$s, '
                    . 'which is needed as a backup for any possible missing strings in the translation for %2$s: %3$s.'),
                $englishTranslationFile,
                \Locale::getDisplayLanguage($this->parameterData["BASE_LOCALE"], $this->parameterData["BASE_LOCALE"]),
                json_last_error_msg()
            );
            self::produceErrorResponse(StatusCode::SERVICE_UNAVAILABLE, $message);
        }

        // if all went well
        $this->RESPONSE->messages[] = sprintf(
            /**translators: 1. count, 2. filename, 3. language */
            _('%1$d data events were loaded from the English translation file %2$s, which is needed as a backup for any possible missing strings in the translation for %3$s.'),
            count(get_object_vars($translationDataEnglish)),
            $englishTranslationFile,
            \Locale::getDisplayLanguage($this->parameterData["BASE_LOCALE"], $this->parameterData["BASE_LOCALE"])
        );
        return $translationDataEnglish;
    }

    private function readData()
    {
        $sourceDataFile = "data/LITURGY__anniversaries.json";
        $sourceData = $this->loadSourceData($sourceDataFile);

        $translationFile = "data/i18n/{$this->parameterData["BASE_LOCALE"]}.json";
        $translationData = $this->loadTranslationData($translationFile);

        $translationEventsCount = count(get_object_vars($translationData));
        $srcDataEventsCount = count($sourceData->anniversary_events);
        if ($translationEventsCount !== $srcDataEventsCount) {
            $message = sprintf(
                /**translators: 1. filename, 2. count, 3. filename, 4. count */
                _('Events count from the translation file %1$s (%2$d) does not match the events count from the base data file %3$s (%4$d).'),
                $translationFile,
                $translationEventsCount,
                $sourceDataFile,
                $srcDataEventsCount
            );
            self::produceErrorResponse(StatusCode::SERVICE_UNAVAILABLE, $message);
        }

        // Use English as a backup for empty strings when a non English locale has been requested
        $translationDataEnglish = null;
        if ($this->parameterData["BASE_LOCALE"] !== 'en') {
            $englishTranslationFile = "data/i18n/en.json";
            $translationDataEnglish = $this->loadEnglishTranslationData($englishTranslationFile);
            $translationEnglishEventsCount = count(get_object_vars($translationDataEnglish));
            if ($translationEnglishEventsCount !== $srcDataEventsCount) {
                $message = sprintf(
                    /**translators: 1. filename, 2. count, 3. filename, 4. count */
                    _('Events count from the English translation file %1$s (%2$d) does not match the events count from the base data file %3$s (%4$d).'),
                    $englishTranslationFile,
                    $translationEnglishEventsCount,
                    $sourceDataFile,
                    $srcDataEventsCount
                );
                self::produceErrorResponse(StatusCode::SERVICE_UNAVAILABLE, $message);
            }
        }

        $englishResultsUsed = false;

        foreach ($translationData as $label => $rowLocalizedData) {
            list( $event_key, $event_idx ) = explode("_", $label);
            foreach ($rowLocalizedData as $rowProperty => $rowValue) {
                if (
                    gettype($rowValue) === 'string'
                    && '' === $rowValue
                    && $translationDataEnglish !== null
                    && property_exists($translationDataEnglish, $label)
                    && property_exists($translationDataEnglish->$label, $rowProperty)
                    && '' !== $translationDataEnglish->$label->$rowProperty
                ) {
                    $englishResultsUsed = true;
                    $rowLocalizedData->$rowProperty = $translationDataEnglish->$label->$rowProperty;
                    $this->RESPONSE->messages[] = "Using English translation for $label.$rowProperty";
                }
            }

            foreach ($sourceData->anniversary_events as $item) {
                if ($item->event_key === $event_key && $item->event_idx === intval($event_idx)) {
                    $anniversaryEvent = new AnniversaryEvent(array_merge((array) $item, (array) $rowLocalizedData));
                    if ($anniversaryEvent->event_year !== null && $this->isAnniversary($anniversaryEvent)) {
                        $this->RESPONSE->anniversary_events[] = $anniversaryEvent;
                    }
                }
            }
            //TODO: we currently sort by liturgical memorial day / month, because that's the data we started with,
            //      however it would be more useful, once the data is defined, to sort by event day / month
            // This is kind of magic :D
            $props = [
                "memorial_month" => 2,
                "memorial_day"   => 1
            ];
            usort($this->RESPONSE->anniversary_events, function ($a, $b) use ($props) {
                foreach ($props as $key => $val) {
                    // if event A's memorialMonth is equal to event B's memorialMonth,
                    // or event B's memorialDay is equal to event B's memorial Day,
                    // no sorting needed (continue checking the next property)
                    // if both properties are equal for both events we will wind up returning 0 = no sort
                    if ($a->$key == $b->$key) {
                        continue;
                    }
                    // if instead event A's memorialMonth is greater than event B's memorialMonth
                    // we will give the memorialMonth a greater sort weight compared to the memorialDay by returning 2
                    // if A's memorialMonth is less than B's memorialMonth we return -2
                    // if A's memorialDay is greater than B's memorialDay we return 1
                    // if A's memorialDay is less than B's memorialDay we return -1
                    return $a->$key > $b->$key ? $val : -($val);
                }
                return 0;
            });
        }

        $this->RESPONSE->messages[] = sprintf(
            /**translators: 1. count, 2. year */
            _('%1$d liturgical anniversary events were calculated for the year %2$d.'),
            count($this->RESPONSE->anniversary_events),
            $this->parameterData["YEAR"]
        );
        if ($englishResultsUsed) {
            $this->RESPONSE->messages[] = sprintf(
                /**translators: 1. language */
                _('English translation strings were used because the translation for %1$s was incomplete.'),
                \Locale::getDisplayLanguage($this->parameterData["BASE_LOCALE"], $this->parameterData["BASE_LOCALE"])
            );
            $this->RESPONSE->incomplete_translation = true;
        }
    }

    private function isAnniversary(AnniversaryEvent $anniversaryEvent): bool
    {
        $yearDiff = $this->parameterData["YEAR"] - $anniversaryEvent->event_year;
        $anniversaryEvent->setYearDiff($yearDiff);

        foreach (array_keys(AnniversaryEvent::ANNIVERSARY) as $key) {
            if (in_array($key, self::RECURRING)) {
                if ($key === "CENTENARY") {
                    if ($yearDiff % AnniversaryEvent::ANNIVERSARY["CENTENARY"] === 0) {
                        $anniversaryEvent->setAnniversary(AnniversaryEvent::ANNIVERSARY["CENTENARY"]);
                        return true;
                    }
                }

                $lastTwoDigits = substr((string)$yearDiff, -2);
                if ($key === array_search((int)$lastTwoDigits, AnniversaryEvent::ANNIVERSARY)) {
                    $anniversaryEvent->setAnniversary(AnniversaryEvent::ANNIVERSARY[$key]);
                    return true;
                }
            } else {
                $arraySearch = array_search($yearDiff, AnniversaryEvent::ANNIVERSARY);
                if ($arraySearch !== false) {
                    $anniversaryEvent->setAnniversary($yearDiff);
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
