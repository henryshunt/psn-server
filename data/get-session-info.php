<?php
date_default_timezone_set("UTC");
include_once("../resources/routines/helpers.php");
include_once("../resources/routines/config.php");

$setup_error = false;
if (!isset($_GET["sessionId"])) $setup_error = true;
$config = new Config();
if (!$config->load_config("../config.ini"))
    $setup_error = true;
$db_connection = database_connection($config);
if (!$db_connection)
    $setup_error = true;
if ($setup_error) { echo "false"; exit(); }


// Get the information about this session
$QUERY = "SELECT name, description from sessions WHERE session_id = ?";
$result = query_database($db_connection, $QUERY, [$_GET["sessionId"]]);
if ($result === false || $result === NULL) { echo $result; exit(); }
echo json_encode($result[0]);