<?php

namespace PayIDValidator;

class PayloadManager
{
    /**
     * Options of ways to alter the response payload
     */
    const INCOMPATIBLE_INVALID_CONTENT_TYPE_HEADER = 1;
    const INCOMPATIBLE_MISSING_CORS_HEADERS = 2;
    const INCOMPATIBLE_INVALID_CORS_HEADERS = 4;
    const INCOMPATIBLE_INVALID_CACHE_CONTROL_HEADER = 8;
    const INCOMPATIBLE_MALFORMED_JSON_BODY = 16;
    const INCOMPATIBLE_WRONG_NETWORK_PROPERTY = 32;
    const INCOMPATIBLE_MISSING_NETWORK_PROPERTY = 64;
    const BEST_PRACTICE_MISSING_PAYID_ROOT = 128;
    const BEST_PRACTICE_MISMATCHED_PAYID_ROOT = 256;

    /**
     * Property to hold the payload
     *
     * @var array
     */
    private $payload;

    /**
     * Property to hold the bitwise selection from the request
     *
     * @var int
     */
    private $bitwiseSelection;

    /**
     * Public constructor
     */
    public function __construct(array $payload, int $bitwiseSelection)
    {
        $this->payload = $payload;
        $this->bitwiseSelection = $bitwiseSelection;

        $this->setResponseHeaders();
        $this->adjustJsonPayload();
    }

    /**
     * Method to set the headers on the response
     */
    private function setResponseHeaders()
    {
        if ($this->bitwiseSelection & self::INCOMPATIBLE_INVALID_CONTENT_TYPE_HEADER) {
            header('Content-Type: text/html');
        } else {
            header('Content-Type: application/json');
        }

        // Set the headers needed for the response
        if ($this->bitwiseSelection & self::INCOMPATIBLE_MISSING_CORS_HEADERS) {
            // we are not setting the CORs headers if this bitwise bit is flipped
        } else if ($this->bitwiseSelection & self::INCOMPATIBLE_INVALID_CORS_HEADERS) {
            header('Access-Control-Allow-Headers: PayID-Version-Bar');
            header('Access-Control-Allow-Methods: POST');
            header('Access-Control-Allow-Origin: foo.com');
            header('Access-Control-Expose-Headers: PayID-Server-Version-Bar, PayID-Version-Bar');
        } else {
            header('Access-Control-Allow-Headers: PayID-Version');
            header('Access-Control-Allow-Methods: GET, OPTIONS');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Expose-Headers: PayID-Server-Version, PayID-Version');
        }

        if ($this->bitwiseSelection & self::INCOMPATIBLE_INVALID_CACHE_CONTROL_HEADER) {
            header('Cache-Control: max-age=60');
        } else {
            header('Cache-Control: no-store');
        }

        header('PayID-Server-Version: 1.0');
    }

    /**
     * Method to adjust the JSON payload according to selected options
     */
    private function adjustJsonPayload()
    {
        // Should we remove the "payId" node from the root?
        if ($this->bitwiseSelection & self::BEST_PRACTICE_MISSING_PAYID_ROOT) {
            unset($this->payload['payId']);
        } else if ($this->bitwiseSelection & self::BEST_PRACTICE_MISMATCHED_PAYID_ROOT) {
            // This is for a mismatched payId value
            $this->payload['payId'] = 'mismatch$payidvalidator.com';
        }

        // In this case we are changing to a wrong network type for all addresses
        if ($this->bitwiseSelection & self::INCOMPATIBLE_WRONG_NETWORK_PROPERTY) {
            foreach ($this->payload['addresses'] as $i => $address) {
                $this->payload['addresses'][$i]->paymentNetwork = 'foobar';
            }
        } else if ($this->bitwiseSelection & self::INCOMPATIBLE_MISSING_NETWORK_PROPERTY) {
            foreach ($this->payload['addresses'] as $i => $address) {
                unset($this->payload['addresses'][$i]->paymentNetwork);
            }
        }
    }

    /**
     * Method to return the altered payload
     */
    public function deliverPayload()
    {
        $payload = json_encode($this->payload);

        if ($this->bitwiseSelection & self::INCOMPATIBLE_MALFORMED_JSON_BODY) {
            // For this selection we will just remove the first character of the response body string
            $payload = substr($payload, 1);
        }

        return $payload;
    }
}
