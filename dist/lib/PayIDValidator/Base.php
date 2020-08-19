<?php

namespace PayIDValidator;

use Jose\Component\Core\JWK;
use Jose\Component\Signature\Serializer\JSONGeneralSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\ES256K;
use Jose\Component\Signature\Algorithm\ES512;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\Algorithm\RS512;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Core\AlgorithmManager;

class Base
{

    /**
     * Supported networks
     */
    const NETWORK_ALL = 'all';

    const NETWORK_ACH = 'ach';

    const NETWORK_BTC_MAINNET = 'btc-mainnet';
    const NETWORK_BTC_TESTNET = 'btc-testnet';

    const NETWORK_ETH_MAINNET = 'eth-mainnet';
    const NETWORK_ETH_ROPSTEN = 'eth-ropsten';
    const NETWORK_ETH_KOVAN = 'eth-kovan';
    const NETWORK_ETH_RINKEBY = 'eth-rinkeby';

    const NETWORK_ILP_MAINNET = 'ilp-mainnet';
    const NETWORK_ILP_TESTNET = 'ilp-testnet';

    const NETWORK_XRP_MAINNET = 'xrpl-mainnet';
    const NETWORK_XRP_TESTNET = 'xrpl-testnet';
    const NETWORK_XRP_DEVNET = 'xrpl-devnet';

    /**
     * Supported response expected choices
     */
    const RESPONSE_200 = 200;
    const RESPONSE_404 = 404;

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
     * The user agent sent with all requests
     */
    const USER_AGENT = 'PayIDValidator.com / 0.1.0';

    /**
     * Regex pattern for a valid PayID
     */
    const PAYID_REGEX = '/^[a-z0-9!#@%&*+\=?^_`{|}~-]+(?:\.[a-z0-9!#@%&*+\=?^_`{|}~-]+)*\$(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z-]*[a-z0-9])?|(?:[0-9]{1,3}\.){3}[0-9]{1,3})$/';

    /**
     * The predefined payment networks supported
     *
     * @var array
     */
    private $networkTypes = [
        self::NETWORK_BTC_MAINNET => [
            'label' => 'BTC (mainnet)',
            'header' => 'application/btc-mainnet+json',
            'hostname' => 'https://blockchain.info'
        ],
        self::NETWORK_BTC_TESTNET => [
            'label' => 'BTC (testnet)',
            'header' => 'application/btc-testnet+json',
            'hostname' => 'https://testnet.blockchain.info',
        ],
        self::NETWORK_ETH_MAINNET => [
            'label' => 'ETH (mainnet)',
            'header' => 'application/eth-mainnet+json',
            'hostname' => 'https://api.etherscan.io',
        ],
        self::NETWORK_ETH_ROPSTEN => [
            'label' => 'ETH (ropsten)',
            'header' => 'application/eth-ropsten+json',
            'hostname' => 'https://api-ropsten.etherscan.io',
        ],
        self::NETWORK_ETH_KOVAN => [
            'label' => 'ETH (kovan)',
            'header' => 'application/eth-kovan+json',
            'hostname' => 'https://api-kovan.etherscan.io',
        ],
        self::NETWORK_ETH_RINKEBY => [
            'label' => 'ETH (rinkeby)',
            'header' => 'application/eth-rinkeby+json',
            'hostname' => 'https://api-rinkkeby.etherscan.io',
        ],
        self::NETWORK_ILP_MAINNET => [
            'label' => 'ILP (mainnet)',
            'header' => 'application/interledger-mainnet+json',
        ],
        self::NETWORK_ILP_TESTNET => [
            'label' => 'ILP (testnet)',
            'header' => 'application/interledger-testnet+json',
        ],
        self::NETWORK_XRP_MAINNET => [
            'label' => 'XRP (mainnet)',
            'header' => 'application/xrpl-mainnet+json',
            'hostname' => 'https://s1.ripple.com:51234',
        ],
        self::NETWORK_XRP_TESTNET => [
            'label' => 'XRP (testnet)',
            'header' => 'application/xrpl-testnet+json',
            'hostname' => 'https://s.altnet.rippletest.net:51234',
        ],
        self::NETWORK_XRP_DEVNET => [
            'label' => 'XRP (devnet)',
            'header' => 'application/xrpl-devnet+json',
            'hostname' => 'https://s.devnet.rippletest.net:51234',
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

    private $responseExpectedTypes = [
        self::RESPONSE_200 => [
            'label' => 'HTTP 200 / OK',
        ],
        self::RESPONSE_404 => [
            'label' => 'HTTP 404 / Not Found',
        ],
    ];

    /**
     * User provided values to complete a request
     */
    private $payId = '';
    private $networkType = '';
    private $responseTypeExpected = self::RESPONSE_200;

    /**
     * Property to toggle if validation has occurred
     *
     * @var bool
     */
    private $hasValidationOccurred = false;

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
     * @var \JsonSchema\Validator
     */
    private $jsonValidator;

    /**
     * Property to hold the logger
     *
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * Property to hold the error message to show to the user in the event a lookup cannot be done.
     *
     * @var string
     */
    private $failError = '';

    /**
     * Property to hold the API key for Etherscan.io
     *
     * @var string
     */
    private $etherscanApiKey;

    /**
     * Property to hold the API key for Blockchain.com
     *
     * @var string
     */
    private $blockchainApiKey;

    /**
     * Property to hold the state of debugMode
     */
    private $debugMode = false;

    /**
     * Property to hold the Guzzle response object
     *
     * @var \GuzzleHttp\Psr7\Response
     */
    private $response;

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
        string $networkType,
        string $responseTypeExpected
    ) {
        $this->payId = $payId;
        $this->networkType = $networkType;
        $this->responseTypeExpected = $responseTypeExpected;
    }

    /**
     * Returns an array of all request types that are defined
     */
    public function getAllNetworkEnvironmentTypes(): array
    {
        return $this->networkTypes;
    }

    /**
     * Returns an array of all response types supported
     */
    public function getAllResponseExpectedTypes(): array
    {
        return $this->responseExpectedTypes;
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
     * Method to get the user defined expected response type
     */
    public function getExpectedResponseType(): int
    {
        return $this->responseTypeExpected;
    }

    /**
     * Method to return if the user defined PayID is of a valid format
     */
    public function isUserDefinedPayIdValid(): bool
    {
        preg_match(
            self::PAYID_REGEX,
            $this->payId,
            $matches
        );

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
        if (!isset($this->networkTypes[$this->networkType])) {
            $this->errors[] = 'The Network provided is not valid.';
            return false;
        }

        return true;
    }

    /**
     * Method to check that the user defined response type is one that is supported
     */
    public function isUserDefinedExpectedResponseSupported(): bool
    {
        if (!isset($this->responseExpectedTypes[$this->responseTypeExpected])) {
            $this->errors[] = 'The Expected Response type provided is not valid.';
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
        $this->isUserDefinedExpectedResponseSupported();

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

        try {
            $client = new \GuzzleHttp\Client();
            $this->response = $client->request(
                'GET',
                $this->getRequestUrl(),
                [
                    'connect_timeout' => 5,
                    'headers' => [
                        'Accept' => $this->networkTypes[$this->networkType]['header'],
                        'PayID-Version' => '1.0',
                        'User-Agent' => self::USER_AGENT,
                    ],
                    'http_errors' => false,
                    'on_stats' => function (\GuzzleHttp\TransferStats $stats) {
                        $this->checkResponseTime($stats->getTransferTime());
                    },
                    'timeout' => 10,
                    'verify' => false,
                    'version' => 2.0,
                ]
            );
        } catch (\GuzzleHttp\Exception\ConnectException $exception) {
            $this->logger->critical($exception);
            $this->failError = $exception->getMessage();
            return false;
        }

        $this->checkStatusCode();
        $this->checkAdminApiBlocked();

        if ($this->response->getStatusCode() === 200) {
            $this->checkCORSHeaders();
            $this->checkCacheControl();
            $this->checkContentType();
            $this->checkResponseBodyForValidity();
            $this->checkVerifiedAddresses();
            $this->checkResponseBodyForNetworkAndEnvironmentCorrectness();
        }

        $this->hasValidationOccurred = true;

        return true;
    }

    /**
     * Method to do the check on the status code
     */
    private function checkStatusCode()
    {
        if ($this->response->getStatusCode() === $this->getExpectedResponseType()) {
            $code = self::VALIDATION_CODE_PASS;
        } else {
            $code = self::VALIDATION_CODE_FAIL;
        }

        $this->setResponseProperty(
            'HTTP Status Code',
            $this->response->getStatusCode(),
            $code
        );
    }

    /**
     * Method to do the check to see if the admin API is blocked/secured
     */
    private function checkAdminApiBlocked()
    {
        $payIdPieces = explode('$', $this->getPayId());
        $aSuccess = false;

        $hostnames = [
            'https://' . $payIdPieces[1] . ':8081/users',
        ];

        foreach ($hostnames as $hostname) {
            try {
                $client = new \GuzzleHttp\Client();
                $response = $client->request(
                    'POST',
                    $hostname,
                    [
                        'connect_timeout' => 3,
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'PayID-Version' => '1.0',
                            'User-Agent' => self::USER_AGENT,
                        ],
                        'http_errors' => false,
                        'json' => [
                            'payId' => 'alice$127.0.0.1',
                            'addresses' => [
                                [
                                    'paymentNetwork' => 'XRPL',
                                    'environment' => 'TESTNET',
                                    'details' => [
                                        'address' => 'rDk7FQvkQxQQNGTtfM2Fr66s7Nm3k87vdS',
                                        'tag' => '123',
                                    ]
                                ],
                            ],
                        ],
                        'timeout' => 5,
                        'verify' => false,
                        'version' => 2.0,
                    ]
                );

                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    $aSuccess = true;

                    $this->setResponseProperty(
                        'Admin API Exposed Check',
                        $hostname,
                        self::VALIDATION_CODE_FAIL,
                        'A POST request was made to this endpoint and a 200-level HTTP resoonse code was returned.'
                    );
                }
            } catch (\GuzzleHttp\Exception\ConnectException $exception) {
                // This is a good sign, could not connect on the given hostname/port combination
            }
        }

        if (!$aSuccess) {
            $this->setResponseProperty(
                'Admin API Exposed Check',
                '',
                self::VALIDATION_CODE_PASS,
                'The admin API to create users was checked and not found to be publicly exposed.'
            );
        }
    }

    /**
     * Method to check for CORS headers
     */
    private function checkCORSHeaders()
    {
        $headerValue = $this->response->getHeaderLine('access-control-allow-origin');

        if (!$this->response->hasHeader('access-control-allow-origin')) {
            $this->setResponseProperty(
                'Header Check / Access-Control-Allow-Origin',
                '',
                self::VALIDATION_CODE_FAIL,
                'The header could not be located in the response.'
            );
        } elseif ($headerValue != '*') {
            $this->setResponseProperty(
                'Header Check / Access-Control-Allow-Origin',
                $headerValue,
                self::VALIDATION_CODE_FAIL,
                'The header has an incorrect value.'
            );
        } else {
            $this->setResponseProperty(
                'Header Check / Access-Control-Allow-Origin',
                $headerValue,
                self::VALIDATION_CODE_PASS
            );
        }

        if (!$this->response->hasHeader('access-control-allow-methods')) {
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

            $headerValue = $this->response->getHeaderLine('access-control-allow-methods');
            $methodValues = explode(',', $headerValue);
            $methodValues = array_map('trim', $methodValues);
            $methodErrors = [];
            $msg = '';

            foreach ($methods as $method) {
                if (!in_array($method, $methodValues)) {
                    $wasFound = false;

                    if ($method === 'OPTIONS') {
                        // Some hosts only return OPTIONS value when
                        // performing a pre-flight request with an OPTIONS request.
                        $wasFound = $this->performSecondaryOptionsHeaderCheck();

                        if ($wasFound) {
                            $msg = 'Method [OPTIONS] was found via a secondary OPTIONS pre-flight request.';
                        }
                    }

                    if (!$wasFound) {
                        $methodErrors[] = 'Method [' . $method . '] not supported.';
                    }
                }
            }

            if (count($methodErrors)) {
                $this->setResponseProperty(
                    'Header Check / Access-Control-Allow-Methods',
                    $headerValue,
                    self::VALIDATION_CODE_FAIL,
                    implode(' ', $methodErrors),
                    $msg
                );
            } else {
                $this->setResponseProperty(
                    'Header Check / Access-Control-Allow-Methods',
                    $headerValue,
                    self::VALIDATION_CODE_PASS,
                    $msg
                );
            }
        }

        if (!$this->response->hasHeader('access-control-allow-headers')) {
            $this->setResponseProperty(
                'Header Check / Access-Control-Allow-Headers',
                '',
                self::VALIDATION_CODE_FAIL,
                'The header could not be located in the response.'
            );
        } else {
            $headerValue = $this->response->getHeaderLine('access-control-allow-headers');
            $pieces = explode(',', $headerValue);
            $pieces = array_map('trim', $pieces);
            $pieces = array_map('strtolower', $pieces);

            if (!in_array('payid-version', $pieces)) {
                $this->setResponseProperty(
                    'Header Check / Access-Control-Allow-Headers',
                    $headerValue,
                    self::VALIDATION_CODE_FAIL,
                    'The [PayID-Version] header was not specified.'
                );
            } else {
                $this->setResponseProperty(
                    'Header Check / Access-Control-Allow-Headers',
                    $headerValue,
                    self::VALIDATION_CODE_PASS
                );
            }
        }

        if (!$this->response->hasHeader('access-control-expose-headers')) {
            $this->setResponseProperty(
                'Header Check / Access-Control-Expose-Headers',
                '',
                self::VALIDATION_CODE_FAIL,
                'The header could not be located in the response.'
            );
        } else {
            $headerValue = $this->response->getHeaderLine('access-control-expose-headers');
            $pieces = explode(',', $headerValue);
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
                    $headerValue,
                    self::VALIDATION_CODE_FAIL,
                    implode(' ', $exposedErrors)
                );
            } else {
                $this->setResponseProperty(
                    'Header Check / Access-Control-Expose-Headers',
                    $headerValue,
                    self::VALIDATION_CODE_PASS
                );
            }
        }
    }

    /**
     * Perform a secondary check for OPTIONS value for
     * the Access-Control-Allow-Methods header. Return TRUE
     * if found via OPTIONS request, false if not found.
     */
    private function performSecondaryOptionsHeaderCheck(): bool
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request(
            'OPTIONS',
            $this->getRequestUrl(),
            [
                'connect_timeout' => 5,
                'headers' => [
                    'Accept' => $this->networkTypes[$this->networkType]['header'],
                    'PayID-Version' => '1.0',
                    'User-Agent' => self::USER_AGENT,
                ],
                'http_errors' => false,
                'timeout' => 10,
                'verify' => false,
                'version' => 2.0,
            ]
        );

        if (!$response->hasHeader('access-control-allow-methods')) {
            return false;
        }

        if (stripos($response->getHeaderLine('access-control-allow-methods'), 'OPTIONS') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Method to check for a valid Cache-Control header
     */
    private function checkCacheControl()
    {
        if (!$this->response->hasHeader('cache-control')) {
            $this->setResponseProperty(
                'Header Check / Cache-Control',
                '',
                self::VALIDATION_CODE_FAIL,
                'The header was not set in the response.'
            );
            return;
        }

        $headerValue = $this->response->getHeaderLine('cache-control');

        if (strpos($headerValue, 'no-store') === false) {
            $this->setResponseProperty(
                'Header Check / Cache-Control',
                $headerValue,
                self::VALIDATION_CODE_FAIL,
                'The header value is not correct. Expected value "no-store".'
            );
            return;
        }

        $this->setResponseProperty(
            'Header Check / Cache-Control',
            $headerValue,
            self::VALIDATION_CODE_PASS
        );
    }

    /**
     * Method to do the check the content type header returned
     */
    private function checkContentType()
    {
        if (!$this->response->hasHeader('content-type')) {
            $this->setResponseProperty(
                'Header Check / Content-Type',
                '',
                self::VALIDATION_CODE_FAIL,
                'The header was not sent in the response.'
            );
            return;
        }

        $headerValue = $this->response->getHeaderLine('content-type');
        preg_match(
            '/application\/[\w\-]*[\+]*json/i',
            $headerValue,
            $headerPieces
        );

        if (count($headerPieces)) {
            $this->setResponseProperty(
                'Content Type',
                $headerValue,
                self::VALIDATION_CODE_PASS
            );
            return;
        }

        $this->setResponseProperty(
            'Content Type',
            ((strlen($headerValue)) ? $headerValue: ''),
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
    private function checkResponseBodyForValidity()
    {
        $body = $this->response->getBody();
        $code = self::VALIDATION_CODE_FAIL;
        $msg = 'The response body is NOT valid JSON.';
        $json = json_decode($body);

        if ($json) {
            $validationErrors = $this->validateRootLevelJson($json);
            $json = $this->decodeVerifiedAddressJson($json);
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
    private function checkResponseBodyForNetworkAndEnvironmentCorrectness()
    {
        $body = $this->response->getBody();
        $json = json_decode($body);
        $code = self::VALIDATION_CODE_FAIL;
        $msg = '';
        $requestHeader = $this->networkTypes[$this->networkType]['header'];

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

            if ($this->networkType !== self::NETWORK_ALL) {
                if (isset($json->addresses)) {
                    foreach ($json->addresses as $i => $address) {
                        if (strtolower($address->paymentNetwork) !== strtolower($network)) {
                            $errors[] = 'The paymentNetwork does not match with request header.';
                        }

                        if (isset($address->environment) && strtolower($address->environment) !== strtolower($environment)) {
                            $errors[] = 'The environment does not match with request header.';
                        }
                    }
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
                    $this->validateJsonAddressObject($i, $address, $validationErrors);
            }
        }

        if (isset($json->verifiedAddresses)) {
            foreach ($json->verifiedAddresses as $i => $address) {
                $validationErrors =
                    $this->validateJsonVerifiedAddressObject($i, $address, $validationErrors);
            }
        }

        return $validationErrors;
    }

    /**
     * Method to validate the JSON Address object from the response
     */
    private function validateJsonAddressObject(
        int $index,
        \stdClass $address,
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

                if (isset($address->paymentNetwork) &&
                    isset($address->environment) &&
                    isset($address->addressDetails->address)
                ) {
                    $this->validateCryptoAddress(
                        $index,
                        $address->paymentNetwork,
                        $address->environment,
                        $address->addressDetails->address
                    );
                }
            }
        }

        return $validationErrors;
    }

    /**
     * Method to validate the JSON Verified Address object from the response
     */
    private function validateJsonVerifiedAddressObject(
        int $index,
        \stdClass $address,
        array $validationErrors
    ): array {

        $validationErrors = $this->validateJsonSchema(
            $address,
            'verified-address.json',
            $validationErrors
        );

        if (isset($address->payload)) {
            $payload = json_decode($address->payload);

            if ($payload) {
                $validationErrors = $this->validateJsonSchema(
                    $payload,
                    'verified-address-payload.json',
                    $validationErrors
                );

                if (isset($payload->payIdAddress)) {
                    $validationErrors = $this->validateJsonSchema(
                        $payload->payIdAddress,
                        'address.json',
                        $validationErrors
                    );
                }
            }
        }

        return $validationErrors;
    }

    /**
     * Method to validate a given JSON schema against object
     */
    private function validateJsonSchema(
        \stdClass $object,
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
     * Method to check the response body for the existance of verified
     * addresses and validate the provided signatures
     */
    private function checkVerifiedAddresses()
    {
        $body = $this->response->getBody();
        $json = json_decode($body);

        if ($json) {
            if (isset($json->verifiedAddresses)) {
                foreach ($json->verifiedAddresses as $i => $rawAddress) {
                    $this->verifySignedAddress(
                        $json->payId,
                        $i,
                        $rawAddress
                    );
                }
            }
        }
    }

    /**
     * Method to decide if a lookup of the crypto address to validate it exists can be performed
     */
    private function validateCryptoAddress(
        int $index,
        string $network,
        string $environment,
        string $address
    ) {
        if (strtolower($network) === 'btc') {
            $this->validateBtcAddress(
                $index,
                $network,
                $environment,
                $address
            );
        } elseif (strtolower($network) === 'eth') {
            $this->validateEthAddress(
                $index,
                $network,
                $environment,
                $address
            );
        } elseif (strtolower($network) === 'xrpl') {
            $this->validateXrpAddress(
                $index,
                $network,
                $environment,
                $address
            );
        }
    }

    /**
     * Method to do a lookup on the BTC network to see if an address is valid
     */
    private function validateBtcAddress(
        int $index,
        string $network,
        string $environment,
        string $address
    ) {
        $hostname =
            $this->networkTypes[strtolower($network . '-' . $environment)]['hostname'];

        $client = new \GuzzleHttp\Client();
        $response = $client->request(
            'GET',
            $hostname . '/q/addressbalance/' . $address,
            [
                'connect_timeout' => 2,
                'headers' => [
                    'User-Agent' => self::USER_AGENT,
                ],
                'http_errors' => false,
                'query' => [
                    'api_code' => $this->blockchainApiKey,
                ],
                'timeout' => 5,
                'verify' => false,
                'version' => 2.0,
            ]
        );

        if ($response->getStatusCode() === 200) {
            $body = $response->getBody();

            $this->setResponseProperty(
                'Address[' . $index . '] ledger verification',
                $address,
                self::VALIDATION_CODE_PASS,
                "The address was validated with the network. Current balance: " . $body
            );
        } else {
            $this->setResponseProperty(
                'Address[' . $index . '] ledger verification',
                $address,
                self::VALIDATION_CODE_FAIL,
                'The network could not find the given address.'
            );
        }
    }

    /**
     * Method to do a lookup on the ETH network to see if an address is valid
     */
    private function validateEthAddress(
        int $index,
        string $network,
        string $environment,
        string $address
    ) {
        $hostname =
            $this->networkTypes[strtolower($network . '-' . $environment)]['hostname'];

        $client = new \GuzzleHttp\Client();
        $response = $client->request(
            'GET',
            $hostname . '/api',
            [
                'connect_timeout' => 2,
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => self::USER_AGENT,
                ],
                'http_errors' => false,
                'query' => [
                    'module' => 'account',
                    'action' => 'balance',
                    'address' => $address,
                    'tag' => 'latest',
                    'apikey' => $this->etherscanApiKey,
                ],
                'timeout' => 5,
                'verify' => false,
                'version' => 2.0,
            ]
        );

        $json = json_decode($response->getBody());

        if ($response->getStatusCode() !== 200
            || !$json
            || $json->status === "0"
        ) {
            $this->setResponseProperty(
                'Address[' . $index . '] ledger verification',
                $address,
                self::VALIDATION_CODE_FAIL,
                'The network could not find the given address.'
            );

            return;
        }

        $balance = $json->result;

        $this->setResponseProperty(
            'Address[' . $index . '] ledger verification',
            $address,
            self::VALIDATION_CODE_PASS,
            "The address was validated with the network. Current balance: " . $balance
        );
    }

    /**
     * Method to do a lookup on the XRPL to check if an address is valid
     */
    private function validateXrpAddress(
        int $index,
        string $network,
        string $environment,
        string $address
    ) {
        $hostname =
            $this->networkTypes[strtolower($network . '-' . $environment)]['hostname'];

        // If we have an encoded address let's get the parts to get the underlying account address
        if (substr($address, 0, 1) === 'X') {
            $addressParts = $this->getDecodedXAddressParts($address);
            $address = $addressParts['account'];
        }

        $client = new \GuzzleHttp\Client();
        $response = $client->request(
            'POST',
            $hostname,
            [
                'connect_timeout' => 2,
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => self::USER_AGENT,
                ],
                'http_errors' => false,
                'json' => [
                    'method' => 'account_info',
                    'params' => [
                        [
                            'account' => $address,
                        ]
                    ]
                ],
                'timeout' => 5,
                'verify' => false,
                'version' => 2.0,
            ]
        );

        $json = json_decode($response->getBody());

        if (isset($json->result->error) && $json->result->error === 'actNotFound') {
            $this->setResponseProperty(
                'Address[' . $index . '] ledger verification',
                $address,
                self::VALIDATION_CODE_FAIL,
                'The network could not find the given address.'
            );
        } elseif (isset($json->result->account_data)
            && $json->result->account_data->Account === $address
        ) {
            $this->setResponseProperty(
                'Address[' . $index . '] ledger verification',
                $address,
                self::VALIDATION_CODE_PASS,
                "The address was validated with the network. Current balance: " . $json->result->account_data->Balance
            );
        }
    }

    /**
     * Method to return the parts of an encoded X address
     */
    private function getDecodedXAddressParts($xAddress): array
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request(
            'GET',
            'https://xrpaddress.info/api/decode/' . $xAddress
        );

        $json = json_decode($response->getBody(), true);

        return $json;
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
    public function hasValidationOccurred(): bool
    {
        return $this->hasValidationOccurred;
    }

    /**
     * Method to return a validation score
     */
    public function getValidationScore(): float
    {
        if (!$this->hasValidationOccurred()) {
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
    protected function getJsonValidator(): \JsonSchema\Validator
    {
        $this->jsonValidator = new \JsonSchema\Validator;

        return $this->jsonValidator;
    }

    /**
     * Method to set the logger
     */
    public function setLogger(\Monolog\Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Method to return the response headers
     */
    public function getResponseHeaders(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * Method to set the API key for Etherscan.io
     */
    public function setEtherscanApiKey(string $apiKey)
    {
        $this->etherscanApiKey = $apiKey;
    }

    /**
     * Method to set the API key for Blockchain.com
     */
    public function setBlockchainApiKey(string $apiKey)
    {
        $this->blockchainApiKey = $apiKey;
    }

    /**
     * Method to get the fail error to show to the user
     */
    public function getFailError(): string
    {
        return $this->failError;
    }

    /**
     * The payload inside a verified address is a stringified json value which is not as friendly to view.
     * This method parses the string into JSON if such a field exists.
     */
    private function decodeVerifiedAddressJson(object $json): object
    {
        if (isset($json->verifiedAddresses)) {
            foreach ($json->verifiedAddresses as $i => $verifiedAddress) {
                if (isset($json->verifiedAddresses[$i]->payload)) {
                    $json->verifiedAddresses[$i]->payload = json_decode($verifiedAddress->payload);
                }
            }
        }

        return $json;
    }

    /**
     * Method to validate a verifiedAddress signature
     */
    private function verifySignedAddress(
        string $payId,
        int $index,
        object $verifiedAddress
    ) {

        $manager = new JWSSerializerManager([new JSONGeneralSerializer()]);
        $algorithmManager = new AlgorithmManager([
            new ES256(),
            new ES256K(),
            new ES512(),
            new RS256(),
            new RS512(),
        ]);
        $jwsVerifier = new JWSVerifier($algorithmManager);

        try {
            $payload = json_decode($verifiedAddress->payload);

            if (!isset($payload->payIdAddress)) {
                $this->setResponseProperty(
                    'Verified address[' . $index . '] PayID',
                    '',
                    self::VALIDATION_CODE_FAIL,
                    'The "payIdAddress" property is missing.'
                );
                return;
            }

            $payIdAddress = $payload->payIdAddress;

            if (!isset($payload->sub)) {
                $this->setResponseProperty(
                    'Verified address[' . $index . '] PayID',
                    $payIdAddress->addressDetails->address,
                    self::VALIDATION_CODE_FAIL,
                    'The payload "sub" property is missing.'
                );
                return;
            } elseif ($payload->sub != $payId) {
                $this->setResponseProperty(
                    'Verified address[' . $index . '] PayID',
                    $payIdAddress->addressDetails->address,
                    self::VALIDATION_CODE_FAIL,
                    'The payload "sub" value ' . $payload->sub . ' does not match ' . $payId . '.'
                );
            }

            $jws = $manager->unserialize(json_encode($verifiedAddress));

            foreach ($jws->getSignatures() as $i => $signature) {
                // FIXME this assumes the JWK is included in the protected headers using `jwk` property
                // be specified as a url via the `jku` property
                $jwk = new JWK($signature->getProtectedHeader()["jwk"]);
                $isVerified = $jwsVerifier->verifyWithKey($jws, $jwk, $i);

                if ($isVerified) {
                    $this->setResponseProperty(
                        'Verified address[' . $index . '] PayID signature[' . $i . '] verification',
                        $payIdAddress->addressDetails->address,
                        self::VALIDATION_CODE_PASS,
                        'Address has a valid signature.'
                    );

                    $this->validateXrpAddress(
                        $index,
                        $payIdAddress->paymentNetwork,
                        $payIdAddress->environment,
                        $payIdAddress->addressDetails->address
                    );
                } else {
                    $this->setResponseProperty(
                        'Verified address[' . $index . '] PayID signature[' . $i . '] verification',
                        $payIdAddress->addressDetails->address,
                        self::VALIDATION_CODE_FAIL,
                        'Signature does not match address.'
                    );
                }
            }
        } catch (Exception $exception) {
            $this->setResponseProperty(
                'Verified address[' . $index . '] PayID signature verification',
                json_decode($verifiedAddress->payload)->payIdAddress->addressDetails->address,
                self::VALIDATION_CODE_FAIL,
                'Invalid signature. Error: ' . $exception -> getMessage()
            );
        }
    }
}
