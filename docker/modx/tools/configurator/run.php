<?php

use App\Runner\Runner;

if (PHP_SAPI !== 'cli') {
    die("Error! Cmd line access only!\n");
}

define('MODX_API_MODE', true);

require_once __DIR__ . '/vendor/autoload.php';
require_once(dirname(__FILE__, 3) . '/index.php');

use MODX\Revolution\modX;
use MODX\Revolution\Error\modError;

/**
 * Cabinet REST API connector
 *
 *  @var modX $modx
 */

$modx->services->add('error', new modError($modx));
$modx->error = $modx->services->get('error');

$config = require(__DIR__ . '/config.inc.php');
$runner = new Runner($modx, $config);
$runner->run();