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
$appLogger = new Logger('app');
$appLogger->pushHandler(new ErrorLogHandler());

$payIDValidator = new PayIDValidator(true);
$payIDValidator->setLogger($appLogger);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $payId = trim($_POST['pay-id']);
    $requestType = trim($_POST['request-type']);

    // Add context to the logging for further debugging
    $appLogger->pushProcessor(function ($record) use ($payId, $requestType) {
        $record['extra']['pay-id'] = $payId;
        $record['extra']['request-type'] = $requestType;

        return $record;
    });

    $payIDValidator->setUserDefinedProperties(
        $payId,
        $requestType
    );

    if (!$payIDValidator->hasPreflightErrors()) {

        // This is hacky. The dev environment is not hosted on AWS.
        if (getenv('PAYID_ENVIRONMENT') === 'production') {
            $client = new SecretsManagerClient();
            $result = $client->getSecretValue([
                'SecretId' => 'etherscan',
            ]);
            $payIDValidator->setEtherscanApiKey($result['SecretString']);
        } else {
            $payIDValidator->setEtherscanApiKey('YourApiKeyToken');
        }

        $payIDValidator->makeRequest();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <title>PayID Validator</title>
    <meta name="monetization" content="$ilp.uphold.com/dB6fefJ7xJn3">
</head>

<body class="flex flex-col antialiased font-sans bg-gray-100 min-h-screen">

    <div class="flex-grow">
        <nav class="bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center text-2xl">
                            <img class="inline h-8 mr-2" src="/assets/img/payid-logo.svg" alt="PayID Logo" /> Validator
                        </div>
                    </div>
                </div>
            </div>
        </nav>

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
                                            Request Type
                                        </label>
                                        <select id="request-type" name="request-type" required class="block w-full px-2 py-2 border border-gray-300 rounded-md focus:outline-none focus:shadow-outline-blue focus:border-blue-300 transition duration-150 ease-in-out">
                                            <option value="">Choose request type</option>
                                            <?php $payIdRequestTypes = $payIDValidator->getAllRequestTypes(); ?>
                                            <?php foreach ($payIdRequestTypes as $id => $details) : ?>
                                                <option value="<?php echo $id ?>" <?php echo (($payIDValidator->getNetworkType() === $id) ? 'selected="selected"' : '') ?>>
                                                    <?php echo $details['label']; ?> - <?php echo $details['header']; ?>
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
                                                <?php echo $payIdRequestTypes[$payIDValidator->getNetworkType()]['header']; ?>
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
                                                    <?php if ($validation['code'] === PayIDValidator::VALIDATION_CODE_PASS) : ?>
                                                        <span class="px-3 inline-flex font-semibold rounded-full bg-green-800 text-green-100">
                                                            Pass
                                                        </span>
                                                    <?php elseif ($validation['code'] === PayIDValidator::VALIDATION_CODE_WARN) : ?>
                                                        <span class="px-3 inline-flex font-semibold rounded-full bg-orange-800 text-orange-100">
                                                            Warn
                                                        </span>
                                                    <?php elseif ($validation['code'] === PayIDValidator::VALIDATION_CODE_FAIL) : ?>
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
                                                        <ul>
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

    <footer>
        <div class="bg-white">
            <div class="mx-auto py-8 px-4 flex items-center justify-between">
                <div class="flex justify-center md:order-2">
                    <span class="text-gray-500">Did this validator help you? Consider sending me a tip <a href="https://twitter.com/robertswarthout" target="_blank" class="underline">@robertswarthout</a>.</span>
                    <a href="https://twitter.com/robertswarthout" class="ml-6 text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Twitter</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                        </svg>
                    </a>
                    <a href="https://github.com/rswarthout/payid-validator" class="ml-6 text-gray-400 hover:text-gray-500">
                        <span class="sr-only">GitHub</span>
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
                <div class="mt-0">
                    <p class="text-center text-base leading-6 text-gray-500">
                        Licensed under MIT. Release <a href="https://github.com/rswarthout/payid-validator/commit/<% RELEASE_HASH %>" target="_blank" class="underline"><% RELEASE_HASH %></a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <?php if ($payIDValidator->hasValidationOccurred()): ?>
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
                                                <?php foreach ($payIDValidator->getResponseHeaders() as $key => $value): ?>
                                                    <?php
                                                        if (trim($key) == ''):
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

        <script src="https://unpkg.com/micromodal/dist/micromodal.min.js"></script>
        <script>MicroModal.init();</script>
        <style type="text/css">
            .modal {
                display: none;
            }

            .modal.is-open {
                display: flex;
            }
        </style>
    <?php endif; ?>

</body>

</html>