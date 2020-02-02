<?php
date_default_timezone_set("UTC");
include_once("../resources/routines/helpers.php");
include_once("../resources/routines/config.php");

$setup_error = false;
if (!isset($_GET["id"])) $setup_error = true;
$config = new Config();
if (!$config->load_config("../config.ini"))
    $setup_error = true;
$db_connection = database_connection($config);
if (!$db_connection)
    $setup_error = true;
if ($setup_error) { echo "false"; exit(1); }


// Get the completed nodes for this session (exact opposite of the active nodes)
$QUERY = "SELECT node_id, location, " .
    "(SELECT report_id FROM reports WHERE session_id = session_nodes.session_id AND node_id = session_nodes.node_id ORDER BY time " .
    "DESC LIMIT 1) AS latest_report FROM session_nodes WHERE session_id = ? " .
    "AND NOT (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time) ORDER BY location";

$result = query_database($db_connection, $QUERY, [$_GET["id"]]);
if ($result === false) { echo "false"; exit(1); }

// Get the latest report data for each node
$QUERY = "SELECT time, airt, relh, batv FROM reports WHERE report_id = ?";
for ($i = 0; $i < count($result); $i++)
{
    if ($result[$i]["latest_report"] !== null)
    {
        $result_report = query_database($db_connection, $QUERY, [$result[$i]["latest_report"]]);
        if ($result_report === false || $result_report === NULL) { echo "false"; exit(1); }
        $result[$i]["latest_report"] = $result_report[0];
    }
}

echo json_encode($result);