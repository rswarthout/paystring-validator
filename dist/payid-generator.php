<?php
require 'vendor/autoload.php';

use PayIDValidator\PayloadManager;
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
                                Generate a PayID address with specific issues
                            </h2>
                            <p class="text-center">Easily test different cases within your app</p>
                        </div>

                        <div id="payid-address" class="w-full text-center mx-auto mt-6 text-5xl">
                            payid$payidvalidator.com
                        </div>

                        <div class="w-full max-w-lg mx-auto mt-6">

                            <div class="py-6 px-4 shadow rounded-lg bg-white">

                                <p>The generated address will support requests for the following networks:</p>
                                <ul class="list-inside list-disc mt-2 ml-2 text-sm">
                                    <li>All</li>
                                    <li>ACH</li>
                                    <li>
                                        BTC
                                        <ul class="list-inside list-disc ml-4 space-y-0.25">
                                            <li>MAINNET</li>
                                            <li>TESTNET</li>
                                        </ul>
                                    </li>
                                    <li>
                                        ETH
                                        <ul class="list-inside list-disc ml-4 space-y-0.25">
                                            <li>MAINNET</li>
                                            <li>KOVAN</li>
                                            <li>RINKEBY</li>
                                            <li>ROPSTEN</li>
                                        </ul>
                                    </li>
                                    <li>
                                        Interleder
                                        <ul class="list-inside list-disc ml-4 space-y-0.25">
                                            <li>MAINNET</li>
                                            <li>TESTNET</li>
                                        </ul>
                                    </li>
                                    <li>
                                        XRPL
                                        <ul class="list-inside list-disc ml-4 space-y-0.25">
                                            <li>MAINNET</li>
                                            <li>TESTNET</li>
                                            <li>DEVNET</li>
                                        </ul>
                                    </li>
                                </ul>

                                <h3 class="font-bold mt-6">Options</h3>

                                <ul id="options-list" class="list-inside mt-4 ml-2 space-y-4">
                                    <li>
                                        <div class="flex items-center">
                                            <input id="option-<?php echo PayloadManager::INCOMPATIBLE_INVALID_CONTENT_TYPE_HEADER ?>" value="<?php echo PayloadManager::INCOMPATIBLE_INVALID_CONTENT_TYPE_HEADER ?>" type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out cursor-pointer">
                                            <label for="option-<?php echo PayloadManager::INCOMPATIBLE_INVALID_CONTENT_TYPE_HEADER ?>" class="ml-2 block text-sm leading-5 text-gray-900 cursor-pointer">
                                                Invalid Content-Type Header
                                            </label>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center">
                                            <input id="option-<?php echo PayloadManager::INCOMPATIBLE_MISSING_CORS_HEADERS ?>" value="<?php echo PayloadManager::INCOMPATIBLE_MISSING_CORS_HEADERS ?>" type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out cursor-pointer">
                                            <label for="option-<?php echo PayloadManager::INCOMPATIBLE_MISSING_CORS_HEADERS ?>" class="ml-2 block text-sm leading-5 text-gray-900 cursor-pointer">
                                                Missing CORS Headers
                                            </label>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center">
                                            <input id="option-<?php echo PayloadManager::INCOMPATIBLE_INVALID_CORS_HEADERS ?>" value="<?php echo PayloadManager::INCOMPATIBLE_INVALID_CORS_HEADERS ?>" type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out cursor-pointer">
                                            <label for="option-<?php echo PayloadManager::INCOMPATIBLE_INVALID_CORS_HEADERS ?>" class="ml-2 block text-sm leading-5 text-gray-900 cursor-pointer">
                                                Invalid CORS Headers
                                            </label>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center">
                                            <input id="option-<?php echo PayloadManager::INCOMPATIBLE_INVALID_CACHE_CONTROL_HEADER ?>" value="<?php echo PayloadManager::INCOMPATIBLE_INVALID_CACHE_CONTROL_HEADER ?>" type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out cursor-pointer">
                                            <label for="option-<?php echo PayloadManager::INCOMPATIBLE_INVALID_CACHE_CONTROL_HEADER ?>" class="ml-2 block text-sm leading-5 text-gray-900 cursor-pointer">
                                                Invalid value for Cache-Control header
                                            </label>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center">
                                            <input id="option-<?php echo PayloadManager::INCOMPATIBLE_MALFORMED_JSON_BODY ?>" value="<?php echo PayloadManager::INCOMPATIBLE_MALFORMED_JSON_BODY ?>" type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out cursor-pointer">
                                            <label for="option-<?php echo PayloadManager::INCOMPATIBLE_MALFORMED_JSON_BODY ?>" class="ml-2 block text-sm leading-5 text-gray-900 cursor-pointer">
                                                Malformed JSON body
                                            </label>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center">
                                            <input id="option-<?php echo PayloadManager::INCOMPATIBLE_WRONG_NETWORK_PROPERTY ?>" value="<?php echo PayloadManager::INCOMPATIBLE_WRONG_NETWORK_PROPERTY ?>" type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out cursor-pointer">
                                            <label for="option-<?php echo PayloadManager::INCOMPATIBLE_WRONG_NETWORK_PROPERTY ?>" class="ml-2 block text-sm leading-5 text-gray-900 cursor-pointer">
                                                Wrong <span class="italic">paymentNetwork</span> value inside of address object
                                            </label>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center">
                                            <input id="option-<?php echo PayloadManager::INCOMPATIBLE_MISSING_NETWORK_PROPERTY ?>" value="<?php echo PayloadManager::INCOMPATIBLE_MISSING_NETWORK_PROPERTY ?>" type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out cursor-pointer">
                                            <label for="option-<?php echo PayloadManager::INCOMPATIBLE_MISSING_NETWORK_PROPERTY ?>" class="ml-2 block text-sm leading-5 text-gray-900 cursor-pointer">
                                                Missing <span class="italic">paymentNetwork</span> property inside of address object
                                            </label>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center">
                                            <input id="option-<?php echo PayloadManager::BEST_PRACTICE_MISSING_PAYID_ROOT ?>" value="<?php echo PayloadManager::BEST_PRACTICE_MISSING_PAYID_ROOT ?>" type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out cursor-pointer">
                                            <label for="option-<?php echo PayloadManager::BEST_PRACTICE_MISSING_PAYID_ROOT ?>" class="ml-2 block text-sm leading-5 text-gray-900 cursor-pointer">
                                                Missing <span class="italic">payId</span> property in JSON root
                                            </label>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="flex items-center">
                                            <input id="option-<?php echo PayloadManager::BEST_PRACTICE_MISMATCHED_PAYID_ROOT ?>" value="<?php echo PayloadManager::BEST_PRACTICE_MISMATCHED_PAYID_ROOT ?>" type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out cursor-pointer">
                                            <label for="option-<?php echo PayloadManager::BEST_PRACTICE_MISMATCHED_PAYID_ROOT ?>" class="ml-2 block text-sm leading-5 text-gray-900 cursor-pointer">
                                                Mismatched <span class="italic">payId</span> property in JSON root
                                            </label>
                                        </div>
                                    </li>
                                </ul>

                                <div class="mt-6">
                                    <span class="block w-full rounded-md shadow-sm">
                                        <button type="buttom" class="w-full flex justify-center py-2 px-4 border border-transparent text-m font-medium rounded-md text-white bg-gray-600 hover:bg-gray-500 focus:outline-none focus:border-gray-700 focus:shadow-outline-gray active:bg-gray-700 transition duration-150 ease-in-out">
                                            Copy PayID Address
                                        </button>
                                    </span>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?php include('./includes/footer.php'); ?>

</body>

</html>