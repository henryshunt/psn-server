<?php
date_default_timezone_set("UTC");
include_once("../resources/routines/helpers.php");
include_once("../resources/routines/config.php");

$setup_error = false;
// if (!isset($_GET["session"])) $setup_error = true;
$config = new Config();
if (!$config->load_config("../config.ini"))
    $setup_error = true;
$db_connection = database_connection($config);
if (!$db_connection)
    $setup_error = true;
if ($setup_error) { echo "false"; exit(1); }


$QUERY = "SELECT time as x, " . $_GET["fields"] . " as y from reports where node_id = ? and session_id = ? and time between ? and ? order by time";

$result = query_database($db_connection, $QUERY, [$_GET["node"], $_GET["session"], date("Y-m-d H:i:s", strtotime($_GET["start"])), date("Y-m-d H:i:s", strtotime($_GET["end"]))]);
if ($result === false) { echo "false"; exit(1); }
if ($result === NULL) { echo "null"; exit(1); }

for ($i = 0; $i < count($result); $i++)
{
    $utc = date_create_from_format("Y-m-d H:i:s", $result[$i]["x"]);

    $result[$i]["x"] = $utc->getTimestamp();
}

echo "[" . json_encode($result) . "]";