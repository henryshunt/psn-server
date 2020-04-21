<?php
/**
 * Returns a downloadable CSV file containing all reports from a node in a session
 */

require_once("../resources/routines/helpers.php");
require_once("../resources/routines/config.php");

if (!isset($_GET["sessionId"])) die("false");
if (!isset($_GET["nodeId"])) die("false");
$config = new Config();
if (!$config->load_config("../config.ini"))
    die("false");
$db_connection = database_connection($config);
if (!$db_connection) die("false");

$session = try_loading_session($db_connection);
if ($session === FALSE || $session === NULL)
    die("false");


$QUERY = "SELECT time, airt, relh, batv FROM reports WHERE session_id = ? AND node_id = ?";
$result = query_database($db_connection, $QUERY, [$_GET["sessionId"], $_GET["nodeId"]]);

if ($result === FALSE) die("false");

// Make this PHP file respond with a CSV file download
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=psn_data.csv");

$csv_output = fopen("php://output", "w");
fputcsv($csv_output, array("time", "airt", "relh", "batv"));
if ($result === NULL) die("");

// Output retrieved reports in CSV format
foreach ($result as $report)
    fputcsv($csv_output, $report);
fclose($csv_output);