<?php
/**
 * Gets the nodes inside a session that are currently active.
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


$QUERY = "SELECT node_id, location, " .
    "(SELECT report_id FROM reports WHERE session_id = session_nodes.session_id AND node_id = session_nodes.node_id " .
    "ORDER BY time DESC LIMIT 1) AS latest_report_id FROM session_nodes WHERE session_id = ? " .
    "AND (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time) ORDER BY location";

$result = query_database($db_connection, $QUERY, [$_GET["sessionId"]]);
if ($result === FALSE || $result === NULL) die(json_encode($result));

// Get the latest report data for each node
$QUERY = "SELECT time, airt, relh, batv FROM reports WHERE report_id = ?";
for ($i = 0; $i < count($result); $i++)
{
    if ($result[$i]["latest_report_id"] !== null)
    {
        $result_report = query_database(
            $db_connection, $QUERY, [$result[$i]["latest_report_id"]]);
        if ($result_report === FALSE || $result_report === NULL) die("false");
        $result[$i]["latest_report"] = $result_report[0];
    }
    else $result[$i]["latest_report"] = NULL;
    
    unset($result[$i]["latest_report_id"]);
}

echo json_encode($result);