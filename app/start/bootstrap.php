<?php

require '../vendor/autoload.php';

$config = require 'config.php';
Config::load($config);

require 'database.php';
require 'commands.php';