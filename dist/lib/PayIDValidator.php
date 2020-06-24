<?php

class PayIDValidator {

    /**
     * Supported networks
     */
    const NETWORK_ACH = 'ach';
    const NETWORK_ALL = 'all';
    const NETWORK_BTC_MAINNET = 'btc-mainnet';
    const NETWORK_ETH_MAINNET = 'eth-mainnet';
    const NETWORK_ETH_TESTNET = 'eth-testnet-name';
    const NETWORK_XRP_MAINNET = 'xrp-mainnet';
    const NETWORK_XRP_TESTNET = 'xrp-testnet';
    const NETWORK_XRP_DEVNET = 'xrp-devnet';

    /**
     * AddressDetailsType values
     */
    const ADDRESS_DETAILS_TYPE_ACH = 'AchAddressDetails';
    const ADDRESS_DETAILS_TYPE_CRYPTO = 'CryptoAddressDetails';

    /**
     * Validation codes
     */
    const VALIDATION_CODE_PASS = 'pass';
    const VALIDATION_CODE_WARN = 'warn';
    const VALIDATION_CODE_FAIL = 'fail';

    /**
     * Regex pattern for a valid PayID
     */
    const PAYID_REGEX = '/^[a-z0-9!#@%&*+\=?^_`{|}~-]+(?:\.[a-z0-9!#@%&*+\=?^_`{|}~-]+)*\$(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z-]*[a-z0-9])?|(?:[0-9]{1,3}\.){3}[0-9]{1,3})$/';

    /**
     * The predefined payment networks supported
     * 
     * @var array
     */
    private $requestTypes = [
        self::NETWORK_BTC_MAINNET => [
            'label' => 'BTC (mainnet)',
            'header' => 'application/btc-mainnet+json',
        ],
        self::NETWORK_ETH_MAINNET => [
            'label' => 'ETH (mainnet)',
            'header' => 'application/eth-mainnet+json',
        ],
        self::NETWORK_ETH_TESTNET => [
            'label' => 'ETH (testnet)',
            'header' => 'application/eth-testnet-name+json',
        ],
        self::NETWORK_XRP_MAINNET => [
            'label' => 'XRP mainnet',
            'header' => 'application/xrpl-mainnet+json',
        ],
        self::NETWORK_XRP_TESTNET => [
            'label' => 'XRP testnet',
            'header' => 'application/xrpl-testnet+json',
        ],
        self::NETWORK_XRP_DEVNET => [
            'label' => 'XRP devnet',
            'header' => 'application/xrpl-devnet+json',
        ],
        self::NETWORK_ACH => [
            'label' => 'ACH',
            'header' => 'application/ach+json',
        ],
        self::NETWORK_ALL => [
            'label' => 'All',
            'header' => 'application/payid+json',
        ],
    ];

    /**
     * User provided values to complete a request
     */
    private $payId = '';
    private $networkType = '';

    /**
     * Property to toggle if validation has occured
     * 
     * @var bool
     */
    private $hasValidationOccured = false;

    /**
     * Array to hold the various errors messages
     * 
     * @var array
     */
    private $errors = [];

    /**
     * Request response properties
     * 
     * @var array
     */
    private $responseProperties = [];

    /**
     * Property to hold the JSON schema validator
     * 
     * @var JsonSchema\Validator
     */
    private $jsonValidator;

    /**
     * Property to hold the logger
     * 
     * @var Monolog\Logger
     */
    private $logger;

    /**
     * Property to hold the state of debugMode
     */
    private $debugMode = false;

    /**
     * Public constructor
     */
    public function __construct(bool $debugMode = false)
    {
        $this->debugMode = $debugMode;
    }

    /**
     *  Set's the user defined PayID and network type
     */
    public function setUserDefinedProperties(
        string $payId, 
        string $networkType
    ) {
        $this->payId = $payId;
        $this->networkType = $networkType;
    }

    /**
     * Returns an array of all request types that are defined
     */
    public function getAllRequestTypes(): array
    {
        return $this->requestTypes;
    }

    /**
     *  Method to get the errors messages 
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Method to get the user defined PayID
     */
    public function getPayId(): string 
    {
        return $this->payId;
    }

    /**
     * Method to get the user defined network type
     */
    public function getNetworkType(): string 
    {
        return $this->networkType;
    }

    /**
     * Method to return if the user defined PayID is of a valid format
     */
    public function isUserDefinedPayIdValid(): bool
    {
        preg_match(
            self::PAYID_REGEX,
            $this->payId, 
            $matches);

        if (count($matches) !== 1) {
            $this->errors[] = 'The PayID you specified (' . $this->payId . ') is not a valid format for a PayID.';
            return false;
        }

        return true;
    }

    /**
     * Method to check that the network type is one that is supported
     */
    public function isUserDefinedNetworkSupported(): bool
    {
        if (!isset($this->requestTypes[$this->networkType])) {
            $this->errors[] = 'The Request Type provided is not valid.';
            return false;
        }

        return true;
    }

    /**
     * Method to check for any preflight errors like an invalid PayID or request type
     */
    public function hasPreflightErrors(): bool
    {
        $this->isUserDefinedPayIdValid();
        $this->isUserDefinedNetworkSupported();

        if (count($this->getErrors())) {
            return true;
        }

        return false;
    }

    /**
     * Method to return the request URL for the validation
     */
    public function getRequestUrl(): string
    {
        $payIdPieces = explode('$', $this->getPayId());
        
        return 'https://' . $payIdPieces[1] . '/' . $payIdPieces[0];
    }

    /** 
     * Method to make the request to the PayID server
     */
    public function makeRequest(): bool
    {
        if ($this->debugMode) {
            $this->logger->info('validation started');
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT => 'PayIDValidator.com / 0.1.0',
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HEADER => true,
            CURLOPT_URL => $this->getRequestUrl(),
            CURLOPT_HTTPHEADER => [
                'PayID-Version: 1.0',
                'Accept: ' . $this->requestTypes[$this->networkType]['header'],
            ],
        ]);
        $response = curl_exec($curl);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headerStrings = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        $info = curl_getinfo($curl);
        curl_close ($curl);

        $this->checkStatusCode($info['http_code']);

        $headers = $this->parseResponseHeaders($headerStrings); 
        $this->checkCORSHeaders($headers);

        if ($info['http_code'] === 200) {
            $this->checkContentType($info);
            $this->checkResponseTime($info['total_time']);
            $this->checkResponseBodyForValidity($body);
            $this->checkResponseBodyForNetworkAndEnvironmentCorrectness($body);
        }
        
        $this->hasValidationOccured = true;

        return true;
    }

    /**
     * Method to do the check on the status code
     */
    private function checkStatusCode(int $statusCode)
    {
        $code = self::VALIDATION_CODE_FAIL;

        if ($statusCode === 200) {
            $code = self::VALIDATION_CODE_PASS;
        } elseif ($statusCode >= 300 && $statusCode < 400) {
            $code = self::VALIDATION_CODE_WARN;
        }

        $this->setResponseProperty(
            'Status Code',
            $statusCode,
            $code
        );
    }

    /**
     * Method to check for CORS headers
     */
    private function checkCORSHeaders(array $headers)
    {
        // format the header keys into lowercase
        $headers = array_change_key_case($headers, CASE_LOWER);

        if (!isset($headers['access-control-allow-origin'])) {
            $this->setResponseProperty(
                'Header Check / Access-Control-Allow-Origin',
                '',
                self::VALIDATION_CODE_FAIL,
                'The header could not be located in the response.'
            );
        } elseif ($headers['access-control-allow-origin'] != '*') {
            $this->setResponseProperty(
                'Header Check / Access-Control-Allow-Origin',
                $headers['access-control-allow-origin'],
                self::VALIDATION_CODE_FAIL,
                'The header has an incorrect value.'
            );
        } else {
            $this->setResponseProperty(
                'Header Check / Access-Control-Allow-Origin',
                $headers['access-control-allow-origin'],
                self::VALIDATION_CODE_PASS
            );
        }

        if (!isset($headers['access-control-allow-methods'])) {
            $this->setResponseProperty(
                'Header Check / Access-Control-Allow-Methods',
                '',
                self::VALIDATION_CODE_FAIL,
                'The header could not be located in the response.'
            );
        } else {

            $methods = [
                'POST', 
                'GET', 
                'OPTIONS',
            ];

            $methodValues = explode(',', $headers['access-control-allow-methods']);
            $methodValues = array_map('trim', $methodValues);
            $methodErrors = [];
            
            foreach ($methods as $method) {
                if (!in_array($method, $methodValues)) {
                    $methodErrors[] = 'Method [' . $method . '] not supported.';
                }
            }

            if (count($methodErrors)) {
                $this->setResponseProperty(
                    'Header Check / Access-Control-Allow-Methods',
                    $headers['access-control-allow-methods'],
                    self::VALIDATION_CODE_FAIL,
                    implode(' ', $methodErrors)
                );
            } else {
                $this->setResponseProperty(
                    'Header Check / Access-Control-Allow-Methods',
                    $headers['access-control-allow-methods'],
                    self::VALIDATION_CODE_PASS
                );
            }
        }

        if (!isset($headers['access-control-allow-headers'])) {
            $this->setResponseProperty(
                'Header Check / Access-Control-Allow-Headers',
                '',
                self::VALIDATION_CODE_FAIL,
                'The header could not be located in the response.'
            );
        } else {

            $pieces = explode(',', $headers['access-control-allow-headers']);
            $pieces = array_map('trim', $pieces);
            $pieces = array_map('strtolower', $pieces);

            if (!in_array('payid-version', $pieces)) {
                $this->setResponseProperty(
                    'Header Check / Access-Control-Allow-Headers',
                    $headers['access-control-allow-headers'],
                    self::VALIDATION_CODE_FAIL,
                    'The [PayID-Version] header was not specified.'
                );
            } else {
                $this->setResponseProperty(
                    'Header Check / Access-Control-Allow-Headers',
                    $headers['access-control-allow-headers'],
                    self::VALIDATION_CODE_PASS
                );
            }
        }

        if (!isset($headers['access-control-expose-headers'])) {
            $this->setResponseProperty(
                'Header Check / Access-Control-Expose-Headers',
                '',
                self::VALIDATION_CODE_FAIL,
                'The header could not be located in the response.'
            );
        } else {

            $pieces = explode(',', $headers['access-control-expose-headers']);
            $pieces = array_map('trim', $pieces);
            $pieces = array_map('strtolower', $pieces);

            $exposed = [
                'PayID-Version',
                'PayID-Server-Version',
            ];

            $exposedErrors = [];
            
            foreach ($exposed as $header) {
                if (!in_array(strtolower($header), $pieces)) {
                    $exposedErrors[] = 'Header [' . $header . '] not included.';
                }
            }

            if (count($exposedErrors)) {
                $this->setResponseProperty(
                    'Header Check / Access-Control-Expose-Headers',
                    $headers['access-control-expose-headers'],
                    self::VALIDATION_CODE_FAIL,
                    implode(' ', $exposedErrors)
                );
            } else {
                $this->setResponseProperty(
                    'Header Check / Access-Control-Expose-Headers',
                    $headers['access-control-expose-headers'],
                    self::VALIDATION_CODE_PASS
                );
            }
        }
    }
    
    /**
     * Method to do the check the content type header returned
     */
    private function checkContentType(array $headers)
    {
        if (!isset($headers['content_type'])) {
            $this->setResponseProperty(
                'Header Check / Content-Type',
                '',
                self::VALIDATION_CODE_FAIL,
                'The header was not sent in the response.'
            );
            return;
        }

        preg_match(
                '/application\/[\w\-]*[\+]*json/i', 
                $headers['content_type'], 
                $headerPieces
        );

        if (count($headerPieces)) {
            $this->setResponseProperty(
                'Content Type',
                $headers['content_type'],
                self::VALIDATION_CODE_PASS
            );
            return;
        }

        $this->setResponseProperty(
            'Content Type',
            ((strlen($headers['content_type'])) ? $headers['content_type']: ''),
            self::VALIDATION_CODE_FAIL,
            'The value of [application/json] or other variants could not be found.'
        );
    }

    /**
     * Method to check the response time of the request
     */
    private function checkResponseTime(float $time)
    {
        $code = self::VALIDATION_CODE_FAIL;
        $msg = 'If the request attempt took more than 5 seconds to complete, it was aborted.';

        if ($time < 5) {
            $code = self::VALIDATION_CODE_PASS;
            $msg = null;
        }

        $this->setResponseProperty(
            'Response Time',
            $time . ' seconds',
            $code,
            $msg
        );
    }

    /**
     * Method to do validation checks on the response body
     */
    private function checkResponseBodyForValidity(string $body)
    {
        $code = self::VALIDATION_CODE_FAIL;
        $msg = 'The response body is NOT valid JSON.';
        $json = json_decode($body);

        if ($json) {
            $validationErrors = $this->validateRootLevelJson($json);
            $body = '<pre>'. str_replace("\n", "<br>", json_encode($json, JSON_PRETTY_PRINT)) . '</pre>';

            if (count($validationErrors)) {
                $code = self::VALIDATION_CODE_FAIL;
                $msg = $validationErrors;
            } else {
                $code = self::VALIDATION_CODE_PASS;
                $msg = 'The response body is valid JSON.';  
            }
        } else {
            // Considering we know this is not valid JSON we are protecting the user here.
            $body = strip_tags($body);
        }

        $this->setResponseProperty(
            'Response Body JSON',
            $body,
            $code,
            $msg
        );
    }

    /**
     * Method to check that the requested network type matches the response 
     */
    private function checkResponseBodyForNetworkAndEnvironmentCorrectness(string $body)
    {
        $json = json_decode($body);
        $code = self::VALIDATION_CODE_FAIL;
        $msg = '';
        $requestHeader = $this->requestTypes[$this->networkType]['header'];

        if ($json) {

            preg_match(
                '/application\/([\w]+)[\-]*([^\+]+)?\+([\w]+)/',
                $requestHeader,
                $headerPieces
            );

            if (count($headerPieces) != 4) {
                $this->setResponseProperty(
                    'Response Body Matches Requested Network',
                    'The requested network type cannot be found.',
                    self::VALIDATION_CODE_FAIL
                );
                return;
            }

            $errors = [];
            $network = $headerPieces[1];
            $environment = $headerPieces[2];

            foreach ($json->addresses as $i => $address) {
                if (strtolower($address->paymentNetwork) !== strtolower($network)) {
                    $errors[] = 'The paymentNetwork does not match with request header.';
                }

                if (isset($address->environment) && strtolower($address->environment) !== strtolower($environment)) {
                    $errors[] = 'The environment does not match with request header.';
                }
            }
            
            if (count($errors)) {
                $code = self::VALIDATION_CODE_FAIL;
                $msg = $errors;
            } else {
                $code = self::VALIDATION_CODE_PASS; 
            }
        }

        $this->setResponseProperty(
            'Response Body Addresses Match Requested Headers',
            $requestHeader,
            $code,
            $msg
        );
    }

    /**
     * Method to validate the root level of the JSON response
     */
    private function validateRootLevelJson($json): array
    {
        $validationErrors = [];

        $validationErrors = $this->validateJsonSchema(
            $json,
            'payment-information.json',
            $validationErrors
        );

        if (isset($json->addresses)) {
            foreach ($json->addresses as $i => $address) {
                $validationErrors = 
                    $this->validateJsonAddressObject($address, $validationErrors);
            }
        }

        return $validationErrors;
    }

    /**
     * Method to validate the JSON Address object from the response
     */
    private function validateJsonAddressObject(
        stdClass $address, 
        array $validationErrors
    ): array {

        $validationErrors = $this->validateJsonSchema(
            $address,
            'address.json',
            $validationErrors
        );

        if (isset($address->addressDetailsType) && isset($address->addressDetails)) {
            if ($address->addressDetailsType === self::ADDRESS_DETAILS_TYPE_ACH) {
                $validationErrors = $this->validateJsonSchema(
                    $address->addressDetails,
                    'ach-address-details.json',
                    $validationErrors
                );
            } else if ($address->addressDetailsType === self::ADDRESS_DETAILS_TYPE_CRYPTO) { 
                $validationErrors = $this->validateJsonSchema(
                    $address->addressDetails,
                    'crypto-address-details.json',
                    $validationErrors
                );
            }
        }

        return $validationErrors;
    }
    
    /**
     * Method to validate a given JSON schema against object
     */
    private function validateJsonSchema(
        stdClass $object,
        string $schemaFile, 
        array $validationErrors
    ): array {

        $validator = $this->getJsonValidator();
        $validator->validate(
            $object, 
            [
                '$ref' => 'file://' . realpath('./schemas/' . $schemaFile)
            ]
        );

        if (!$validator->isValid()) {
            foreach ($validator->getErrors() as $error) {
                $validationErrors[] = 
                    sprintf("[%s] %s", $error['property'], $error['message']);
            }
        }

        return $validationErrors;
    }

    /**
     * Method to add properties to the response rules stack
     */
    private function setResponseProperty(
        string $label, 
        string $value, 
        string $code, 
        $msg = null
    ) {
        $this->responseProperties[] = [
            'label' => $label,
            'value' => $value,
            'code' => $code,
            'msg' => $msg,
        ];

        if ($this->debugMode) {
            $this->logger->info(
                strtolower($label), 
                [
                    'value' => $value,
                    'code' => $code,
                    'msg' => $msg,
                ]
            );
        }
    }

    /**
     * Method to return the different response checks
     */
    public function getResponseProperties(): array
    {
        return $this->responseProperties;
    }

    /**
     * Method to return if validation has occured
     */
    public function hasValidationOccured(): bool
    {
        return $this->hasValidationOccured;
    }

    /**
     * Method to return a validation score
     */
    public function getValidationScore(): float
    {
        if (!$this->hasValidationOccured()) {
            return 0.0;
        }

        $score = 0;
        $entries = count($this->getResponseProperties());
        $passPoints = 2;
        $warnPoints = 1;
        $failPoints = 0;

        foreach ($this->getResponseProperties() as $validation) {
            if ($validation['code'] === self::VALIDATION_CODE_PASS) {
                $score += $passPoints;
            } elseif ($validation['code'] === self::VALIDATION_CODE_WARN) {
                $score += $warnPoints;
            } elseif ($validation['code'] === self::VALIDATION_CODE_FAIL) {
                // No points for a fail!
                $score += $failPoints;
            }
        }

        $score = round(($score / ($entries * $passPoints)) * 100, 2);

        if ($this->debugMode) {
            $this->logger->info('validation score', [$score]);
        }

        return $score;
    }

    /**
     * Method to get the JSON validator
     */
    protected function getJsonValidator(): JsonSchema\Validator
    {
        if ($this->jsonValidator === null) {
            $this->jsonValidator = new JsonSchema\Validator;
        }

        return $this->jsonValidator;
    }

    /**
     * Method to parse a string of headers
     */
    private function parseResponseHeaders(string $headersString): array
    {
        $headers = [];
        
        $headerStrings = explode("\n", $headersString);

        foreach ($headerStrings as $headerString) {
            $pieces = explode(':', $headerString, 2);
            $headers[trim($pieces[0])] = 
                (isset($pieces[1]) ? trim($pieces[1]) : '');
        }

        return $headers;
    } 

    /**
     * Method to set the logger
     */
    public function setLogger(Monolog\Logger $logger)
    {
        $this->logger = $logger;
    }
}