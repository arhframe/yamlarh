<?php
ini_set("variables_order", "EGPCS");
error_reporting(E_ALL & ~E_NOTICE);
date_default_timezone_set('GMT');
$loader = require_once __DIR__ . '/../vendor/autoload.php';
$loader->add('Arhframe\\Yamlarh\\', __DIR__);

return $loader;