<?php

use App\Runner\Runner;

if (PHP_SAPI !== 'cli') {
    die("Error! Cmd line access only!\n");
}

define('MODX_API_MODE', true);

require_once __DIR__ . '/vendor/autoload.php';
require_once(dirname(__FILE__, 3) . '/index.php');

/** @var \modX $modx */
$modx->getService('error', 'error.modError');
$modx->setLogLevel(\modX::LOG_LEVEL_ERROR);
$modx->setLogTarget('FILE');

$config = require(__DIR__ . '/config.inc.php');
$runner = new Runner($modx, $config);
$runner->run();