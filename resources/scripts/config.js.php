<?php
/**
 * Outputs JavaScript containing some back end config options to allow them
 * to be used on the front end.
 */

require_once "../../php/helpers.php";

$config = load_configuration("../../config.json");
if ($config === false)
    exit();

echo "var configTimeZone = '" . $config["local_time_zone"] . "';";