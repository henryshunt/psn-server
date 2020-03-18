<?php
date_default_timezone_set("UTC");
include_once("../resources/routines/helpers.php");
include_once("../resources/routines/config.php");

$setup_error = false;
if (!isset($_GET["nodeId"])) $setup_error = true;
if (!isset($_GET["sessionId"])) $setup_error = true;
if (!isset($_GET["start"])) $setup_error = true;
if (!isset($_GET["end"])) $setup_error = true;
if (!isset($_GET["field"])) $setup_error = true;
$config = new Config();
if (!$config->load_config("../config.ini"))
    $setup_error = true;
$db_connection = database_connection($config);
if (!$db_connection)
    $setup_error = true;
if ($setup_error) { echo "false"; exit(); }


$QUERY = "SELECT time AS x, " . $_GET["field"] . " AS y FROM reports WHERE node_id = ? AND session_id = ? AND time BETWEEN ? AND ? ORDER BY time";

$result = query_database($db_connection, $QUERY, [$_GET["nodeId"], $_GET["sessionId"],
    date("Y-m-d H:i:s", strtotime($_GET["start"])), date("Y-m-d H:i:s", strtotime($_GET["end"]))]);
if ($result === false || $result === NULL) die(json_encode($result));

for ($i = 0; $i < count($result); $i++)
{
    $time = date_create_from_format("Y-m-d H:i:s", $result[$i]["x"]);
    $result[$i]["x"] = $time->getTimestamp();
}

echo "[" . json_encode($result) . "]";