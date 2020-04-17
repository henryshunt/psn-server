<?php
/**
 * Gets the reports for the preceding X number of intervals leading up to a time.
 */

require_once("../resources/routines/helpers.php");
require_once("../resources/routines/config.php");

if (!isset($_GET["nodeId"])) die("false");
if (!isset($_GET["sessionId"])) die("false");
if (!isset($_GET["time"])) die("false");
if (!isset($_GET["amount"])) die("false");
$config = new Config();
if (!$config->load_config("../config.ini"))
    die("false");
$db_connection = database_connection($config);
if (!$db_connection) die("false");

$session = try_loading_session($db_connection);
if ($session === FALSE || $session === NULL)
    die("false");


$QUERY = "SELECT reports.time, reports.airt, reports.relh, reports.batv FROM reports INNER JOIN session_nodes " .
    "ON session_nodes.session_id = reports.session_id AND session_nodes.node_id = reports.node_id " .
    "WHERE reports.session_id = ? AND reports.node_id = ? " .
    "AND time BETWEEN DATE_SUB(?, INTERVAL (? * session_nodes.interval) MINUTE) AND ? ORDER BY time DESC LIMIT ?";

$time = date("Y-m-d H:i:ss", strtotime($_GET["time"]));
$result = query_database($db_connection, $QUERY, [$_GET["sessionId"], $_GET["nodeId"],
    $time, $_GET["amount"], $time, $_GET["amount"]]);
echo json_encode($result);