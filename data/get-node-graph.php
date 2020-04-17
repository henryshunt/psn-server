<?php
/**
 * Returns all points in a specific time period for a specific field, formatted for graph display.
 */

require_once("../resources/routines/helpers.php");
require_once("../resources/routines/config.php");

if (!isset($_GET["nodeId"])) die("false");
if (!isset($_GET["sessionId"])) die("false");
if (!isset($_GET["start"])) die("false");
if (!isset($_GET["end"])) die("false");
if (!isset($_GET["field"])) die("false");
$config = new Config();
if (!$config->load_config("../config.ini"))
    die("false");
$db_connection = database_connection($config);
if (!$db_connection) die("false");

$session = try_loading_session($db_connection);
if ($session === FALSE || $session === NULL)
    die("false");


$QUERY = "SELECT UNIX_TIMESTAMP(time) AS x, " . $_GET["field"] . " AS y FROM reports " .
    "WHERE node_id = ? AND session_id = ? AND time BETWEEN ? AND ? ORDER BY time";

$result = query_database($db_connection, $QUERY, [$_GET["nodeId"], $_GET["sessionId"],
    date("Y-m-d H:i:s", strtotime($_GET["start"])), date("Y-m-d H:i:s", strtotime($_GET["end"]))]);

if ($result !== FALSE || $result !== NULL)
    echo "[" . json_encode($result) . "]";
else echo json_encode($result);