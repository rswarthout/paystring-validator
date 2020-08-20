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

                    <div
                        x-data="watchOptions()"
                        x-init="updateAddress()"
                        class="flex flex-col justify-center">
                        <div>
                            <h2 class="mt-2 lg:mt-6 text-center text-3xl font-extrabold text-gray-900">
                                Generate a PayID address with specific issues
                            </h2>
                            <p class="text-center">Easily test different cases within your app</p>
                        </div>

                        <div
                            id="payid-address"
                            x-text="address"
                            class="w-full text-center mx-auto mt-6 text-5xl">
                        </div>

                        <div class="w-full max-w-lg mx-auto mt-6">

                            <div class="py-6 px-4 shadow rounded-lg bg-white">

                                <p>The generated address will support requests for the following networks:</p>
                                <div class="flex mb-4">
                                    <div class="w-1/3">
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
                                        </ul>
                                    </div>
                                    <div class="w-1/3">
                                        <ul class="list-inside list-disc mt-2 ml-2 text-sm">
                                            <li>
                                                ETH
                                                <ul class="list-inside list-disc ml-4 space-y-0.25">
                                                    <li>MAINNET</li>
                                                    <li>KOVAN</li>
                                                    <li>RINKEBY</li>
                                                    <li>ROPSTEN</li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="w-1/3">
                                        <ul class="list-inside list-disc mt-2 ml-2 text-sm">
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
                                    </div>
                                </div>

                                <h3 class="font-bold mt-6">Incompatible Options</h3>

                                <ul class="list-inside mt-4 ml-2 space-y-4">
                                    <?php foreach (PayloadManager::INCOMPATIBLE_OPTIONS as $id => $details) : ?>
                                        <li>
                                            <div class="flex items-center">
                                                <input
                                                    id="option-<?php echo $id; ?>"
                                                    value="<?php echo $id; ?>"
                                                    x-on:click="updateAddress()"
                                                    type="checkbox"
                                                    class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out cursor-pointer">
                                                <label for="option-<?php echo $id; ?>" class="ml-2 block text-sm leading-5 text-gray-900 cursor-pointer">
                                                    <?php echo $details['label']; ?>
                                                </label>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <h3 class="font-bold mt-6">Best Practice Options</h3>

                                <ul class="list-inside mt-4 ml-2 space-y-4">
                                    <?php foreach (PayloadManager::BEST_PRACTICE_OPTIONS as $id => $details) : ?>
                                        <li>
                                            <div class="flex items-center">
                                                <input
                                                    id="option-<?php echo $id; ?>"
                                                    value="<?php echo $id; ?>"
                                                    x-on:click="updateAddress()"
                                                    type="checkbox"
                                                    class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out cursor-pointer">
                                                <label for="option-<?php echo $id; ?>" class="ml-2 block text-sm leading-5 text-gray-900 cursor-pointer">
                                                    <?php echo $details['label']; ?>
                                                </label>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="mt-6">
                                    <span class="block w-full rounded-md shadow-sm">
                                        <button
                                            type="buttom"
                                            @click="copyAddressToClipboard()"
                                            class="w-full flex justify-center py-2 px-4 border border-transparent text-m font-medium rounded-md text-white bg-gray-600 hover:bg-gray-500 focus:outline-none focus:border-gray-700 focus:shadow-outline-gray active:bg-gray-700 transition duration-150 ease-in-out">
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

    <script>
        function watchOptions() {
            return {
                address: '',
                copyAddressToClipboard() {
                    const elm = document.createElement('textarea');
                    elm.value = this.address;
                    document.body.appendChild(elm);
                    elm.select();
                    document.execCommand('copy');
                    document.body.removeChild(elm);
                },
                updateAddress() {
                    var checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
                    var bitwiseTotal = 0;
                    for (var checkbox of checkboxes) {
                        bitwiseTotal += parseInt(checkbox.value, 10);
                    }

                    if (bitwiseTotal === 0) {
                        this.address = 'payid$payidvalidator.com';
                    } else {
                        this.address = 'payid-' + bitwiseTotal + '$payidvalidator.com';
                    }
                }
            }
        }
    </script>

</body>

</html>