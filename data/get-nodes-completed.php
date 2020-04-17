<?php
/**
 * Gets the nodes inside a session that are not currently active (exact opposite of the active nodes).
 */

require_once("../resources/routines/helpers.php");
require_once("../resources/routines/config.php");

if (!isset($_GET["sessionId"])) die("false");
$config = new Config();
if (!$config->load_config("../config.ini"))
    die("false");
$db_connection = database_connection($config);
if (!$db_connection) die("false");

$session = try_loading_session($db_connection);
if ($session === FALSE || $session === NULL)
    die("false");


$QUERY = "SELECT node_id, location FROM session_nodes WHERE session_id = ? " .
    "AND NOT (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time) ORDER BY location";

$result = query_database($db_connection, $QUERY, [$_GET["sessionId"]]);
echo json_encode($result);