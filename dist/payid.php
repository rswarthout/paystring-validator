<?php
require 'vendor/autoload.php';

$bitwise = (int) $_GET['bitwise'];
$payIdAddress = $_GET['address-prefix'];

if ($bitwise > 0) {
    $payIdAddress .= '-' . $bitwise;
}

$payIdAddress .= '$payidvalidator.com';

$acceptHeaderValue = strtolower($_SERVER['HTTP_ACCEPT']);
preg_match(
    '/application\/([\w\-]*)[\+]*json/i',
    $acceptHeaderValue,
    $headerPieces
);

// If we cannot parse the content-type requested we should just bail
if (count($headerPieces) !== 2) {
    http_response_code(400);
    exit;
}

$headerSubPieces = explode('-', $headerPieces[1]);

if (count($headerSubPieces) === 1 && $headerSubPieces[0] === 'all') {
    $network = null;
    $environment = null;
} else if (count($headerSubPieces) === 1 && $headerSubPieces[0] === 'ach') {
    $network = $headerSubPieces[0];
    $environment = 'default';
} else {
    $network = $headerSubPieces[0];
    $environment = $headerSubPieces[1];
}

// This is an ALL request
if ($network === null) {
    $files = glob('./payid-addresses/*/*.json');
} else {
    // This is a request for specific network/environment combination
    $path = realpath('./payid-addresses/' . $network . '/' . $environment . '.json');

    if (!$path) {
        http_response_code(404);
        exit;
    }

    $files = [$path];
}

// Now let's pull together all of the needed addresses
$addresses = [];

foreach ($files as $filepath) {
    $addresses[] = json_decode(file_get_contents($filepath));
}

$payload = [
    'payId' => $payIdAddress,
    'addresses' => $addresses,
];

// Now, let's adjust this response for any chosen issues based upon the bitwise operator
$payloadManager = new PayIDValidator\PayloadManager(
    $payload,
    $bitwise
);
echo $payloadManager->deliverPayload();
