<?php

use LitCal\AnniversaryCalculator;

// error_reporting(E_ALL);
// ini_set('display_errors', 1);
ini_set('date.timezone', 'Europe/Vatican');
require_once 'vendor/autoload.php';

$calculator = new AnniversaryCalculator();
$calculator->init();
