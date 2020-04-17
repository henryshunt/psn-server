<?php
/**
 * Stops a node inside a session from reporting any longer
 */

require_once("../resources/routines/helpers.php");
require_once("../resources/routines/config.php");

if (!isset($_GET["sessionId"])) die("false");
if (!isset($_GET["nodeId"])) die("false");
$config = new Config();
if (!$config->load_config("../config.ini"))
    die("false");
$db_connection = database_connection($config);
if (!$db_connection) die("false");

$session = try_loading_session($db_connection);
if ($session === FALSE || $session === NULL)
    die("false");


$QUERY = "UPDATE session_nodes SET end_time = NOW() WHERE session_id = ? AND node_id = ? AND NOW() >= start_time AND (end_time = NULL OR NOW() < end_time)";
$result = query_database($db_connection, $QUERY, [$_GET["sessionId"], $_GET["nodeId"]]);
echo $result === FALSE ? "false" : "true";