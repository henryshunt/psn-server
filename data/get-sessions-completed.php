<?php
/**
 * Gets the sessions containing no currently active nodes (exact opposite of the active sessions).
 */

require_once("../resources/routines/helpers.php");
require_once("../resources/routines/config.php");

$config = new Config();
if (!$config->load_config("../config.ini"))
    die("false");
$db_connection = database_connection($config);
if (!$db_connection) die("false");

$session = try_loading_session($db_connection);
if ($session === FALSE || $session === NULL)
    die("false");


if ($session["user_id"] === "admin" || $session["user_id"] === "guest")
{
    $QUERY = "SELECT session_id, name, " .
        "(SELECT start_time FROM session_nodes WHERE session_id = sessions.session_id ORDER BY start_time ASC LIMIT 1) AS start_time, " .
        "(SELECT end_time FROM session_nodes WHERE session_id = sessions.session_id ORDER BY end_time DESC LIMIT 1) AS end_time, " .
        "(SELECT COUNT(*) FROM session_nodes WHERE session_id = sessions.session_id) AS node_count " .
        "FROM sessions HAVING NOT (start_time IS NOT NULL " .
        "AND (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time)) ORDER BY name";
    $result = query_database($db_connection, $QUERY, NULL);
}
else
{
    $QUERY = "SELECT session_id, name, " .
        "(SELECT start_time FROM session_nodes WHERE session_id = sessions.session_id ORDER BY start_time ASC LIMIT 1) AS start_time, " .
        "(SELECT end_time FROM session_nodes WHERE session_id = sessions.session_id ORDER BY end_time DESC LIMIT 1) AS end_time, " .
        "(SELECT COUNT(*) FROM session_nodes WHERE session_id = sessions.session_id) AS node_count " .
        "FROM sessions WHERE user_id = ? HAVING NOT (start_time IS NOT NULL " .
        "AND (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time)) ORDER BY name";
    $result = query_database($db_connection, $QUERY, [$session["user_id"]]);
}

echo json_encode($result);