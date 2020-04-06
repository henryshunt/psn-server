<?php
/**
 * Gets the nodes inside a session that are not currently active (exact opposite of the active nodes).
 */

date_default_timezone_set("UTC");
include_once("../resources/routines/helpers.php");
include_once("../resources/routines/config.php");

$setup_error = FALSE;
$config = new Config();
if (!$config->load_config("../config.ini"))
    $setup_error = true;
$db_connection = database_connection($config);
if (!$db_connection) $setup_error = true;
if ($setup_error) die("false");


$QUERY = "SELECT nodes.node_id, nodes.mac_address FROM nodes";//, session_nodes WHERE " .
    //"NOT (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time) ORDER BY location";

$result = query_database($db_connection, $QUERY, NULL);
echo json_encode($result);