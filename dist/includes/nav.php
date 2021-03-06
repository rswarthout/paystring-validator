<nav class="bg-white">
    <div class="max-w-7xl mx-auto px-2">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center text-xl md:text-2xl">
                    <div class="inline h-8 mr-2 paystring-logo"></div> Validator
                </div>
                <div class="-my-px ml-4 md:ml-6 space-x-2 md:space-x-8 flex">
                    <?php
                        $activeClasses = 'inline-flex items-center text-center px-1 pt-1 border-b-2 border-blue-500 text-sm font-medium leading-5 text-gray-900 focus:outline-none focus:border-blue-700 transition duration-150 ease-in-out';
                        $inactiveClasses = 'inline-flex items-center text-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out';
                    ?>
                    <?php
                    if ($_SERVER['REQUEST_URI'] == '/paystring-generator.php') :
                        $index = $inactiveClasses;
                        $generator = $activeClasses;
                    else :
                        $index = $activeClasses;
                        $generator = $inactiveClasses;
                    endif;
                    ?>
                    <a href="/" class="mamd:mx-auto <?php echo $index; ?>">
                        Validate
                    </a>
                    <a href="/paystring-generator.php" class="<?php echo $generator; ?>">
                        Generator
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>