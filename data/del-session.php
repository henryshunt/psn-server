<?php
/**
 * Deletes a session and everything (including the reports) associated with it
 */

date_default_timezone_set("UTC");
include_once("../resources/routines/helpers.php");
include_once("../resources/routines/config.php");

$setup_error = FALSE;
if (!isset($_GET["sessionId"])) $setup_error = true;
$config = new Config();
if (!$config->load_config("../config.ini"))
    $setup_error = true;
$db_connection = database_connection($config);
if (!$db_connection)
    $setup_error = true;
if ($setup_error) die("false");


$QUERY = "DELETE FROM sessions WHERE session_id = ?";
$result = query_database($db_connection, $QUERY, [$_GET["sessionId"]]);
echo $result === FALSE ? "false" : "true";