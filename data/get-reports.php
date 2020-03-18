<?php
/**
 * Gets information about a node inside a session (location, interval, etc.).
 */

date_default_timezone_set("UTC");
include_once("../resources/routines/helpers.php");
include_once("../resources/routines/config.php");

$setup_error = FALSE;
if (!isset($_GET["nodeId"])) $setup_error = true;
if (!isset($_GET["sessionId"])) $setup_error = true;
if (!isset($_GET["time"])) $setup_error = true;
if (!isset($_GET["amount"])) $setup_error = true;
$config = new Config();
if (!$config->load_config("../config.ini"))
    $setup_error = true;
$db_connection = database_connection($config);
if (!$db_connection)
    $setup_error = true;
if ($setup_error) die("false");


$QUERY = "SELECT reports.time, reports.airt, reports.relh, reports.batv FROM reports INNER JOIN session_nodes " .
    "ON session_nodes.session_id = reports.session_id AND session_nodes.node_id = reports.node_id " .
    "WHERE reports.session_id = ? AND reports.node_id = ? " .
    "AND time BETWEEN DATE_SUB(?, INTERVAL (? * session_nodes.interval) MINUTE) AND ? ORDER BY time DESC LIMIT ?";

$time = date("Y-m-d H:i:ss", strtotime($_GET["time"]));
$result = query_database($db_connection, $QUERY, [$_GET["sessionId"], $_GET["nodeId"],
    $time, $_GET["amount"], $time, $_GET["amount"]]);
echo json_encode($result);