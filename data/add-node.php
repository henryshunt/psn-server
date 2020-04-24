<?php
/**
 * Adds a new sensor node to the sensor network
 */

require_once("../resources/routines/helpers.php");
require_once("../resources/routines/config.php");

if (!isset($_POST["address"])) die("false");
$config = new Config();
if (!$config->load_config("../config.ini"))
    die("false");
$db_connection = database_connection($config);
if (!$db_connection) die("false");

$session = try_loading_session($db_connection);
if ($session === FALSE || $session === NULL)
    die("false");


$QUERY = "INSERT INTO nodes (mac_address) VALUES (?)";
$result = query_database($db_connection, $QUERY, [$_POST["address"]]);
echo $result === FALSE ? "false" : "true";