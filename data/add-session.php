<?php
date_default_timezone_set("UTC");
include_once("../resources/routines/helpers.php");
include_once("../resources/routines/config.php");

$setup_error = false;
if (!isset($_POST["data"])) $setup_error = true;
$config = new Config();
if (!$config->load_config("../config.ini"))
    $setup_error = true;
$db_connection = database_connection($config);
if (!$db_connection)
    $setup_error = true;
if ($setup_error) { echo "false0"; exit(1); }


$data = json_decode($_POST["data"]);

// Lock the table so it can't be modified during pre-insert checks
// $QUERY = "LOCK TABLE session_nodes";
// $result = query_database($db_connection, $QUERY, NULL);
// if ($result === false) { echo "false"; exit(1); }

$query_error = false;

$QUERY = "SELECT 1 FROM session_nodes WHERE node_id = ? " .
    "AND start_time > ? OR end_time IS NULL OR ? BETWEEN start_time AND end_time";
$current_time = time();

// Check if any of the specified nodes are already active
foreach ($data->{"nodes"} as $node)
{
    $result = query_database($db_connection, $QUERY, [$node->{"node"},
        date("Y-m-d H:i:s", $current_time), date("Y-m-d H:i:s", $current_time)]);
    if ($result === false || $result !== NULL) { echo "false1"; exit(1); }
}

// Pre-insert checks completed and raised no problems, so do the insert
$QUERY = "INSERT INTO sessions (name, description) VALUES (?, ?)";
// echo($data->);
// echo "INSERT INTO sessions (name, description) VALUES (" . $data->{"name"} . ", " . $data->{"description"} . ")";
$result = query_database($db_connection, $QUERY, ["a", "b"]);
if ($result === false) { echo "false2"; exit(1); }

$new_session_id = $db_connection->lastInsertId();
echo $new_session_id;
$QUERY = "INSERT INTO session_nodes (session_id, node_id, location, start_time, end_time, " .
    "`interval`, batch_size) VALUES (?, ?, ?, NOW(), ?, ?, ?)";

foreach ($data->{"nodes"} as $node)
{
    echo $node->{"node"};
    $result = query_database($db_connection, $QUERY, [$new_session_id, $node->{"node"},
        $node->{"location"}, $node->{"endTime"}, $node->{"interval"},
        $node->{"batchSize"}]);
    if ($result === false) { echo "false3"; exit(1); }
}

echo "true";

// $QUERY = "UNLOCK TABLES";
// $result = query_database($db_connection, $QUERY, NULL);
// if ($result === false) { echo "false"; exit(1); }