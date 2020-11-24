# PayString Validator
A web app that validates PayString server responses. The intent is to help developers building PayString servers and integrating them into already existing systems. The intent is to be in the same line of thinking as ssllabs.com is for SSL site analysis.

## Requirements
- To compile CSS you will need `npm`.
- PHP 7.1+
  - gmp extension
  - mbstring extension
- To pull in PHP libraries you will need `composer` available.

## List of Current Validation/Checks Performed
- HTTP Status Code
- [CORS Headers](https://docs.paystring.org/paystring-best-practices#set-cors-cross-origin-resource-sharing-headers)
  - Access-Control-Allow-Origin
  - Access-Control-Allow-Methods
  - Access-Control-Allow-Headers
  - Access-Control-Expose-Headers
- Content-Type header check
- Cache-Control header check
- Response Time
- JSON [Schema Validation](https://docs.paystring.org/paystring-interfaces) of response body
- Validation of Address to [Accept](https://docs.paystring.org/paystring-headers#request-headers) header
- Cross-check that each crypto address returned is valid on the given network/environment.
- Check for valid signatures when a response contains a verifiedAddresses property.