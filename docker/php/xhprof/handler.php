<?php

if (extension_loaded('xhprof')) {
    require_once __DIR__ . '/vendor/autoload.php';
    xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
    register_shutdown_function(function () {
        $xhprof_data = xhprof_disable();
        $mongo = new MongoDB\Client(getenv('XHGUI_MONGO_URI'));
        $collection = $mongo->selectCollection(getenv('XHGUI_MONGO_DATABASE'), 'results');
        $data = array(
            'meta' => array(
                'url' => $_SERVER['REQUEST_URI'],
                'get' => $_GET,
                'SERVER' => $_SERVER,
               // 'env' => $_ENV,
                'simple_url' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
                'request_ts' => new MongoDB\BSON\UTCDateTime(microtime(true) * 1000),
                'request_date' => date('Y-m-d'),
            ),
            'profile' => $xhprof_data
        );
        $collection->insertOne($data);
    });
}