<?php
/**
 * Outputs JavaScript containing some back end config options to allow them
 * to be used on the front end.
 */

require_once("../routines/helpers.php");
require_once("../routines/config.php");

$config = new Config();
if (!$config->load_config("../../config.ini"))
    die();
$db_connection = database_connection($config);
if (!$db_connection) die();


echo "var configTimeZone = '" . $config->get_local_time_zone() . "';";