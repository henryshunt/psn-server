<?php
date_default_timezone_set("UTC");
include_once("../resources/routines/helpers.php");
include_once("../resources/routines/config.php");

$setup_error = false;
if (!isset($_GET["session"])) $setup_error = true;
$config = new Config();
if (!$config->load_config("../config.ini"))
    $setup_error = true;
$db_connection = database_connection($config);
if (!$db_connection)
    $setup_error = true;
if ($setup_error) { echo "false"; exit(1); }


// Get the information about this session
$QUERY = "SELECT name, description from sessions WHERE session_id = ?";
$result = query_database($db_connection, $QUERY, [$_GET["session"]]);
if ($result === false) { echo "false"; exit(1); }
if ($result === NULL) { echo "null"; exit(1); }

echo json_encode($result[0]);