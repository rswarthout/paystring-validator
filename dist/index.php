<?php
require 'vendor/autoload.php';

use Aws\SecretsManager\SecretsManagerClient;
use Monolog\ErrorHandler;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;

// Let's setup error/exception handling globally
$phpLogger = new Logger('php');
$phpLogger->pushHandler(new ErrorLogHandler());
ErrorHandler::register($phpLogger);

// Logging to be used wthin the app for debugging
$appLogger = new Logger('app-validate');
$appLogger->pushHandler(new ErrorLogHandler());

$payIDValidator = new PayIDValidator\Base(true);
$payIDValidator->setLogger($appLogger);

$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payId = trim($_POST['pay-id']);
    $requestType = trim($_POST['request-type']);
    $expectedResponseType = (int) trim($_POST['expected-response-type']);

    // Add context to the logging for further debugging
    $appLogger->pushProcessor(function ($record) use ($payId, $requestType) {
        $record['extra']['pay-id'] = $payId;
        $record['extra']['request-type'] = $requestType;

        return $record;
    });

    $payIDValidator->setUserDefinedProperties(
        $payId,
        $requestType,
        $expectedResponseType
    );

    if (!$payIDValidator->hasPreflightErrors()) {
        // This is hacky. The dev environment is not hosted on AWS.
        if (getenv('PAYID_ENVIRONMENT') === 'production') {
            $client = new SecretsManagerClient([
                'region' => getenv('AWS_REGION'),
                'version' => 'latest',
            ]);

            // Get the Etherscan.io API key
            $result = $client->getSecretValue([
                'SecretId' => 'etherscan',
            ]);
            $payIDValidator->setEtherscanApiKey($result['SecretString']);

            // Get the Blockchain.com API Key
            $result = $client->getSecretValue([
                'SecretId' => 'blockchain',
            ]);
            $payIDValidator->setBlockchainApiKey($result['SecretString']);
        } else {
            $payIDValidator->setEtherscanApiKey('YourApiKeyToken');
            $payIDValidator->setBlockchainApiKey('');
        }

        $success = $payIDValidator->makeRequest();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<?php include('./includes/head.php'); ?>

<body class="flex flex-col antialiased font-sans bg-gray-100 min-h-screen">

    <div class="flex-grow">
        <?php include('./includes/nav.php'); ?>

        <div class="py-10">
            <main>
                <div class="max-w-7xl mx-auto px-4">

                    <div class="flex flex-col justify-center">
                        <div>
                            <h2 class="mt-2 lg:mt-6 text-center text-3xl font-extrabold text-gray-900">
                                Validate your PayID server responses
                            </h2>
                        </div>

                        <div class="w-full max-w-md mx-auto mt-6">
                            <div class="py-6 px-4 shadow rounded-lg bg-white">
                                <form method="post">

                                    <?php if (count($payIDValidator->getErrors())) : ?>
                                        <div class="rounded-md bg-red-100 p-4 mb-4">
                                            <div class="flex">
                                                <div class="flex-shrink-0">
                                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <h3 class="text-sm leading-5 font-medium text-red-800">
                                                        There <?php echo ((count($payIDValidator->getErrors()) > 1) ? 'were ' . count($payIDValidator->getErrors()) . ' errors' : 'was 1 error') ?> with your submission
                                                    </h3>
                                                    <div class="mt-2 text-sm leading-5 text-red-700">
                                                        <ul class="list-disc pl-5">
                                                            <?php foreach ($payIDValidator->getErrors() as $i => $error) : ?>
                                                                <li <?php echo (($i > 0) ? 'class="mt-1"' : '') ?>>
                                                                    <?php echo htmlentities($error); ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <label for="pay-id" class="block text-sm font-medium leading-5 text-gray-700">
                                            PayID address
                                        </label>
                                        <div class="mt-1 rounded-md shadow-sm">
                                            <input id="pay-id" name="pay-id" type="text" aria-label="PayID address" placeholder="alice$example.com" required value="<?php echo htmlentities($payIDValidator->getPayId()) ?>" class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:shadow-outline-blue focus:border-blue-300 transition duration-150 ease-in-out" />
                                        </div>
                                    </div>

                                    <div class="mt-6">
                                        <label for="request-type" class="block text-sm font-medium leading-5 text-gray-700">
                                            Network (environment)
                                        </label>
                                        <select id="request-type" name="request-type" required class="block w-full px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:shadow-outline-blue focus:border-blue-300 transition duration-150 ease-in-out">
                                            <option value="">Choose a network type</option>
                                            <?php $payIdNetworkTypes = $payIDValidator->getAllNetworkEnvironmentTypes(); ?>
                                            <?php foreach ($payIdNetworkTypes as $id => $details) : ?>
                                                <option value="<?php echo $id ?>" <?php echo (($payIDValidator->getNetworkType() === $id) ? 'selected="selected"' : '') ?>>
                                                    <?php echo $details['label']; ?> - <?php echo $details['header']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mt-6">
                                        <label for="request-type" class="block text-sm font-medium leading-5 text-gray-700">
                                            Expected Response
                                        </label>
                                        <select id="expected-response-type" name="expected-response-type" required class="block w-full px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:shadow-outline-blue focus:border-blue-300 transition duration-150 ease-in-out">
                                            <?php $responseTypes = $payIDValidator->getAllResponseExpectedTypes(); ?>
                                            <?php foreach ($responseTypes as $id => $details) : ?>
                                                <option value="<?php echo $id ?>" <?php echo (($payIDValidator->getExpectedResponseType() === $id) ? 'selected="selected"' : '') ?>>
                                                    <?php echo $details['label']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mt-6">
                                        <span class="block w-full rounded-md shadow-sm">
                                            <button type="buttom" class="w-full flex justify-center py-2 px-4 border border-transparent text-m font-medium rounded-md text-white bg-green-600 hover:bg-green-500 focus:outline-none focus:border-green-700 focus:shadow-outline-green active:bg-green-700 transition duration-150 ease-in-out">
                                                Validate
                                            </button>
                                        </span>
                                    </div>

                                </form>

                            </div>
                        </div>
                    </div>

                    <?php if ($success === false) : ?>
                        <div class="flex flex-col justify-center">
                            <div class="w-full max-w-xl mx-auto">
                                <div class="bg-red-300 shadow mt-10 px-10 py-8 rounded-lg">
                                    <?php echo $payIDValidator->getFailError(); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!$payIDValidator->hasValidationOccurred()) : ?>
                        <div class="flex flex-col justify-center">
                            <div class="w-full max-w-xl mx-auto">
                                <div class="bg-white shadow mt-10 px-10 py-8 rounded-lg">
                                    <span class="text-3xl font-medium text-gray-900">
                                        Validation / Checks Performed
                                    </span>
                                    <ul class="list-inside list-disc pl-3">
                                        <li>HTTP Status Code</li>
                                        <li>
                                            <a href="https://docs.payid.org/payid-best-practices#set-cors-cross-origin-resource-sharing-headers" target="_blank" class="underline">CORS Headers</a>
                                            <ul class="list-inside list-disc pl-3">
                                                <li>Access-Control-Allow-Origin</li>
                                                <li>Access-Control-Allow-Methods</li>
                                                <li>Access-Control-Allow-Headers</li>
                                                <li>Access-Control-Expose-Headers</li>
                                            </ul>
                                        </li>
                                        <li>Content-Type header check</li>
                                        <li>Cache-Control header check</li>
                                        <li>Response Time</li>
                                        <li>JSON <a href="https://docs.payid.org/payid-interfaces" target="_blank" class="underline">Schema Validation</a> of response body</li>
                                        <li>Validation of Address to <a href="https://docs.payid.org/payid-headers#request-headers" target="_blank" class="underline">Accept</a> header</li>
                                        <li>Cross-check that each crypto address returned is valid on the given network/environment.</li>
                                        <li>Check for valid signatures when a response contains a verifiedAddresses property.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($payIDValidator->hasValidationOccurred()) : ?>
                        <div class="flex flex-col mt-3">
                            <div class="bg-white shadow overflow-hidden rounded-lg">
                                <div class="py-5 border-b border-gray-200 px-6">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                                        Request Details
                                    </h3>
                                    <p class="mt-1 max-w-2xl text-sm leading-5 text-gray-500">
                                        The details of the validation request.
                                    </p>
                                </div>
                                <div class="py-5 px-6">
                                    <dl class="grid col-gap-4 row-gap-8 grid-cols-1 md:grid-cols-2 lg:grid-cols-5">
                                        <div class="col-span-1">
                                            <dt class="text-sm leading-5 font-medium text-gray-500">
                                                Request URL
                                            </dt>
                                            <dd class="mt-1 text-sm leading-5 text-gray-900">
                                                <?php echo $payIDValidator->getRequestUrl(); ?>
                                            </dd>
                                        </div>
                                        <div class="col-span-1">
                                            <dt class="text-sm leading-5 font-medium text-gray-500">
                                                Request Type
                                            </dt>
                                            <dd class="mt-1 text-sm leading-5 text-gray-900">
                                                GET
                                            </dd>
                                        </div>
                                        <div class="col-span-1">
                                            <dt class="text-sm leading-5 font-medium text-gray-500">
                                                HTTP Version
                                            </dt>
                                            <dd class="mt-1 text-sm leading-5 text-gray-900">
                                                2.0
                                            </dd>
                                        </div>
                                        <div class="col-span-1">
                                            <dt class="text-sm leading-5 font-medium text-gray-500">
                                                Header / Accept
                                            </dt>
                                            <dd class="mt-1 text-sm leading-5 text-gray-900">
                                                <?php echo $payIdNetworkTypes[$payIDValidator->getNetworkType()]['header']; ?>
                                            </dd>
                                        </div>
                                        <div class="col-span-1">
                                            <dt class="text-sm leading-5 font-medium text-gray-500">
                                                Header / PayID-Version
                                            </dt>
                                            <dd class="mt-1 text-sm leading-5 text-gray-900">
                                                1.0
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white px-6 py-5 mt-3 border-b border-gray-200 rounded-lg shadow">
                            <div class="-ml-4 -mt-2 flex items-center justify-between flex-wrap sm:flex-no-wrap">
                                <div class="ml-4 mt-2">
                                    <h3 class="text-xl leading-6 font-medium text-gray-900">
                                        Validation Results
                                    </h3>
                                </div>
                                <div class="ml-4 mt-2">
                                    <a href="javascript:;" data-micromodal-trigger="modal-response-headers" class="text-gray-500 underline">View All Response Headers</a>
                                </div>
                                <div class="ml-4 mt-2 flex-shrink-0">
                                    <span class="inline-flex text-xl font-medium">
                                        Score <?php echo $payIDValidator->getValidationScore(); ?>%
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php foreach ($payIDValidator->getResponseProperties() as $i => $validation) : ?>
                            <div class="flex flex-col mt-3">
                                <div class="bg-white shadow overflow-hidden rounded-lg">
                                    <div class="py-5 border-b border-gray-200 px-6">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                                            <?php echo $validation['label']; ?>
                                        </h3>
                                    </div>
                                    <div class="py-5 px-6">
                                        <dl class="grid col-gap-4 row-gap-8 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                                            <div class="col-span-1">
                                                <dt class="text-sm leading-5 font-medium text-gray-500">
                                                    Value
                                                </dt>
                                                <dd class="mt-1 text-sm leading-5 text-gray-900">
                                                    <?php echo $validation['value']; ?>
                                                </dd>
                                            </div>
                                            <div class="col-span-1">
                                                <dt class="text-sm leading-5 font-medium text-gray-500">
                                                    Result
                                                </dt>
                                                <dd class="mt-1 text-sm leading-5 text-gray-900">
                                                    <?php if ($validation['code'] === \PayIDValidator\Base::VALIDATION_CODE_PASS) : ?>
                                                        <span class="px-3 inline-flex font-semibold rounded-full bg-green-800 text-green-100">
                                                            Pass
                                                        </span>
                                                    <?php elseif ($validation['code'] === \PayIDValidator\Base::VALIDATION_CODE_WARN) : ?>
                                                        <span class="px-3 inline-flex font-semibold rounded-full bg-orange-800 text-orange-100">
                                                            Warn
                                                        </span>
                                                    <?php elseif ($validation['code'] === \PayIDValidator\Base::VALIDATION_CODE_FAIL) : ?>
                                                        <span class="px-3 inline-flex font-semibold rounded-full bg-red-800 text-red-100">
                                                            Fail
                                                        </span>
                                                    <?php endif; ?>
                                                </dd>
                                            </div>
                                            <div class="col-span-1">
                                                <dt class="text-sm leading-5 font-medium text-gray-500">
                                                    Message
                                                </dt>
                                                <dd class="mt-1 text-sm leading-5 text-gray-900">
                                                    <?php if (is_array($validation['msg'])) : ?>
                                                        <ul class="list-inside list-disc pl-3">
                                                            <?php foreach ($validation['msg'] as $msg) : ?>
                                                                <li><?php echo $msg; ?></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php else : ?>
                                                        <?php echo ((strlen($validation['msg'])) ? $validation['msg'] : '<span>-</span>'); ?>
                                                    <?php endif; ?>
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </main>
        </div>
    </div>

    <?php include('./includes/footer.php'); ?>

    <?php if ($payIDValidator->hasValidationOccurred()) : ?>
        <style type="text/css">
            .modal {
                display: none;
            }

            .modal.is-open {
                display: flex;
            }
        </style>

        <?php // This is the headers modal ?>
        <div id="modal-response-headers" class="modal fixed bottom-0 inset-x-0 px-4 pb-6 sm:inset-0 sm:p-0 sm:items-center sm:justify-center" aria-hidden="true">
            <div class="fixed inset-0 transition-opacity">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="relative bg-white rounded-lg px-4 pt-5 pb-4 overflow-hidden shadow-xl transform transition-all sm:max-w-4xl sm:w-full sm:p-6" role="dialog" aria-modal="true" aria-labelledby="modal-headline">
                <div>
                    <h3 class="text-lg text-center leading-6 font-medium text-gray-900" id="modal-headline">
                        Response Headers
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm leading-5 text-gray-500">
                            Scroll to see more headers.
                        </p>
                    </div>
                    <div class="mt-2">
                        <p class="text-sm leading-5 text-gray-500">
                            <div class="flex flex-col">
                                <div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                                    <div class="max-h-screen75 inline-block min-w-full shadow overflow-x-hidden overflow-y-auto rounded-lg border-b border-gray-200">
                                        <table class="min-w-full">
                                            <thead>
                                                <tr>
                                                    <th class="px-3 py-3 border-b border-gray-400 bg-gray-400 text-left text-xs leading-4 font-medium text-gray-800 uppercase tracking-wider">
                                                        Header
                                                    </th>
                                                    <th class="px-3 py-3 border-b border-gray-400 bg-gray-400 text-left text-xs leading-4 font-medium text-gray-800 uppercase tracking-wider">
                                                        Value
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $i = 0; ?>
                                                <?php foreach ($payIDValidator->getResponseHeaders() as $key => $value) : ?>
                                                    <?php
                                                    if (trim($key) == '') :
                                                        continue;
                                                    endif;

                                                    $i++
                                                    ?>
                                                    <tr class="<?php echo (($i % 2) ? 'bg-white': 'bg-gray-100') ?>">
                                                        <td class="px-3 py-4 whitespace-no-wrap text-sm leading-5 font-medium text-gray-900">
                                                            <?php echo htmlentities(trim($key)); ?>
                                                        </td>
                                                        <td class="px-3 py-4 text-sm leading-5 text-gray-500">
                                                            <?php echo htmlentities(trim(implode(', ', $value))); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </p>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6">
                    <span class="flex w-full rounded-md shadow-sm">
                        <button type="button" data-custom-close="modal-response-headers" data-micromodal-close class="inline-flex justify-center w-full rounded-md border border-transparent px-4 py-2 bg-indigo-600 text-base leading-6 font-medium text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:border-indigo-700 focus:shadow-outline-indigo transition ease-in-out duration-150 sm:text-sm sm:leading-5">
                            Close
                        </button>
                    </span>
                </div>
            </div>
        </div>

        <?php // This is the CORS headers modal ?>
        <div id="modal-cors-headers" class="modal fixed bottom-0 inset-x-0 px-4 pb-6 sm:inset-0 sm:p-0 sm:items-center sm:justify-center" aria-hidden="true">
            <div class="fixed inset-0 transition-opacity">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <div class="relative bg-white rounded-lg px-4 pt-5 pb-4 overflow-hidden shadow-xl transform transition-all sm:max-w-4xl sm:w-full sm:p-6" role="dialog" aria-modal="true" aria-labelledby="modal-headline">
                <div>
                    <h3 class="text-lg text-center leading-6 font-medium text-gray-900" id="modal-headline">
                        CORS Headers Configuration
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm leading-5 text-gray-500">
                            Apache config
                        </p>
                    </div>
                    <div class="mt-2">
                        <p class="text-xs leading-5 text-gray-500">
                            <div class="flex flex-col">
                                <div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                                    <div class="max-h-screen75 inline-block min-w-full overflow-x-hidden overflow-y-auto rounded-lg border-b bg-gray-300">
<pre class="text-sm p-2">Header set Access-Control-Allow-Origin "*"
Header set Access-Control-Allow-Methods "GET, OPTIONS"
Header set Access-Control-Allow-Headers "PayID-Version"
Header set Access-Control-Expose-Headers "PayID-Version,PayID-Server-Version"
Header set Cache-Control "no-store"</pre>
                                    </div>
                                </div>
                            </div>
                        </p>
                    </div>
                    <div class="mt-2">
                        <p class="text-sm leading-5 text-gray-500">
                            Nginx config
                        </p>
                    </div>
                    <div class="mt-2">
                        <p class="leading-5 text-gray-500">
                            <div class="flex flex-col">
                                <div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                                    <div class="max-h-screen75 inline-block min-w-full overflow-x-hidden overflow-y-auto rounded-lg border-b bg-gray-300">
<pre class="text-sm p-2">location / {
    add_header 'Cache-Control' 'no-store';

    if ($request_method = 'OPTIONS') {
        add_header 'Access-Control-Allow-Origin' '*';
        add_header 'Access-Control-Allow-Methods' 'GET, OPTIONS';
        add_header 'Access-Control-Allow-Headers' 'PayID-Version';
        add_header 'Access-Control-Expose-Headers' 'PayID-Version,PayID-Server-Version';
        return 204;
    }
}</pre>
                                    </div>
                                </div>
                            </div>
                        </p>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6">
                    <span class="flex w-full rounded-md shadow-sm">
                        <button type="button" data-custom-close="modal-response-headers" data-micromodal-close class="inline-flex justify-center w-full rounded-md border border-transparent px-4 py-2 bg-indigo-600 text-base leading-6 font-medium text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:border-indigo-700 focus:shadow-outline-indigo transition ease-in-out duration-150 sm:text-sm sm:leading-5">
                            Close
                        </button>
                    </span>
                </div>
            </div>
        </div>

        <script src="https://unpkg.com/micromodal/dist/micromodal.min.js"></script>
        <script>MicroModal.init();</script>
    <?php endif; ?>

</body>

</html>