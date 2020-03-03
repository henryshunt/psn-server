<?php
date_default_timezone_set("UTC");
include_once("../resources/routines/helpers.php");
include_once("../resources/routines/config.php");

$setup_error = false;
$config = new Config();
if (!$config->load_config("../config.ini"))
    $setup_error = true;
$db_connection = database_connection($config);
if (!$db_connection)
    $setup_error = true;
if ($setup_error) { echo "false"; exit(1); }

// Get the completed sessions (exact opposite of the active sessions)
$QUERY = "SELECT session_id, name, " .
    "(SELECT start_time FROM session_nodes WHERE session_id = sessions.session_id ORDER BY start_time ASC LIMIT 1) AS start_time, " .
    "(SELECT end_time FROM session_nodes WHERE session_id = sessions.session_id ORDER BY end_time DESC LIMIT 1) AS end_time, " .
    "(SELECT COUNT(*) FROM session_nodes WHERE session_id = sessions.session_id) AS node_count " .
    "FROM sessions HAVING NOT (start_time IS NOT NULL " .
    "AND (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time)) ORDER BY name";

$result = query_database($db_connection, $QUERY, NULL);
echo json_encode($result);