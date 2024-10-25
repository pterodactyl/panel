<?php

declare(strict_types=1);

require './vendor/autoload.php';

$files = [
    'big' => './tests/data/big.mo',
    'little' => './tests/data/little.mo',
];

$start = microtime(true);

for ($i = 0; $i < 2000; ++$i) {
    foreach ($files as $domain => $filename) {
        $translator = new PhpMyAdmin\MoTranslator\Translator(
            new PhpMyAdmin\MoTranslator\Cache\ApcuCache(
                new PhpMyAdmin\MoTranslator\MoParser($filename), 'foo', $domain
            )
        );
        $translator->gettext('Column');
    }
}

$end = microtime(true);

$diff = $end - $start;

echo 'Execution took ' . $diff . ' seconds' . "\n";
