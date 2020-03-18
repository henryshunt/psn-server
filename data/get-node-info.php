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
$config = new Config();
if (!$config->load_config("../config.ini"))
    $setup_error = true;
$db_connection = database_connection($config);
if (!$db_connection)
    $setup_error = true;
if ($setup_error) die("false");


$QUERY = "SELECT session_nodes.location, session_nodes.`interval`, session_nodes.batch_size, " .
    "session_nodes.start_time, session_nodes.end_time, sessions.name AS session_name, " .
    "NOW() >= session_nodes.start_time AND (session_nodes.end_time = NULL OR NOW() < session_nodes.end_time) AS is_active " .
    "FROM session_nodes INNER JOIN sessions ON session_nodes.session_id = sessions.session_id " .
    "WHERE session_nodes.session_id = ? AND session_nodes.node_id = ?";
$result = query_database($db_connection, $QUERY, [$_GET["sessionId"], $_GET["nodeId"]]);
echo json_encode($result === FALSE || $result === NULL ? $result : $result[0]);