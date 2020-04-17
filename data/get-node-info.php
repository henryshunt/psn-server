<?php
/**
 * Gets information about a node inside a session (location, interval, etc.).
 */

require_once("../resources/routines/helpers.php");
require_once("../resources/routines/config.php");

if (!isset($_GET["nodeId"])) die("false");
if (!isset($_GET["sessionId"])) die("false");
$config = new Config();
if (!$config->load_config("../config.ini"))
    die("false");
$db_connection = database_connection($config);
if (!$db_connection) die("false");

$session = try_loading_session($db_connection);
if ($session === FALSE || $session === NULL)
    die("false");


$QUERY = "SELECT session_nodes.location, session_nodes.`interval`, session_nodes.batch_size, " .
    "session_nodes.start_time, session_nodes.end_time, sessions.name AS session_name, " .
    "NOW() >= session_nodes.start_time AND (session_nodes.end_time = NULL OR NOW() < session_nodes.end_time) AS is_active " .
    "FROM session_nodes INNER JOIN sessions ON session_nodes.session_id = sessions.session_id " .
    "WHERE session_nodes.session_id = ? AND session_nodes.node_id = ?";
$result = query_database($db_connection, $QUERY, [$_GET["sessionId"], $_GET["nodeId"]]);
echo json_encode($result === FALSE || $result === NULL ? $result : $result[0]);