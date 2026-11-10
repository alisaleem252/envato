<?php
require __DIR__ . '/envato-api-class.php';

// Token from https://build.envato.com/create-token/ (export ENVATO_TOKEN=... or pass it here)
$API = new envatoAPI(getenv('ENVATO_TOKEN') ?: null);

$data = $API->popular('codecanyon');

header('Content-Type: text/plain; charset=utf-8');
if ($data === false) {
    echo 'Error: ' . $API->last_error;
} else {
    print_r($data);
}
