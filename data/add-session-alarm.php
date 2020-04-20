<?php
/**
 * Creates a new session
 */

require_once("../resources/routines/helpers.php");
require_once("../resources/routines/config.php");

if (!isset($_POST["data"])) die("false");
$config = new Config();
if (!$config->load_config("../config.ini"))
    die("false");
$db_connection = database_connection($config);
if (!$db_connection) die("false");

$session = try_loading_session($db_connection);
if ($session === FALSE || $session === NULL)
    die("false");


$data = json_decode($_POST["data"], TRUE);

$QUERY = "INSERT INTO session_alarms (session_id, node_id, parameter, minimum, maximum) " .
    "VALUES (?, ?, ?, ?, ?)";
$result = query_database($db_connection, $QUERY, [$data["sessionId"], $data["nodeId"],
    $data["parameter"], $data["minimum"], $data["maximum"]]);
echo $result === FALSE ? "false" : "true";