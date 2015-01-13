<?php

require '../vendor/autoload.php';

/*
    https://github.com/php/php-src/pull/696
    7 months for a fucking constant
*/
if(!defined('CURLPROXY_SOCKS5_HOSTNAME')) {
    define('CURLPROXY_SOCKS5_HOSTNAME', 7);
}

$config = require 'config.php';
Config::load($config);

require 'database.php';
require 'commands.php';