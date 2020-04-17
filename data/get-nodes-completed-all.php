<?php
/**
 * Gets all nodes that are not currently active in any session.
 */

require_once("../resources/routines/helpers.php");
require_once("../resources/routines/config.php");

$config = new Config();
if (!$config->load_config("../config.ini"))
    die("false");
$db_connection = database_connection($config);
if (!$db_connection) die("false");


$QUERY = "SELECT node_id, mac_address FROM nodes WHERE node_id NOT IN " .
    "(SELECT node_id FROM session_nodes WHERE (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time))";
    
$result = query_database($db_connection, $QUERY, NULL);
echo json_encode($result);