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
if ($session === FALSE) die("false");
if ($session === NULL)
{
    header("Location: login.php");
    exit();
}


$data = json_decode($_POST["data"], TRUE);

// Lock tables so they can't be modified during pre-insert checks
$QUERY = "LOCK TABLE sessions WRITE, session_nodes WRITE";
$result = query_database($db_connection, $QUERY, NULL);
if ($result === FALSE) die("false");

$QUERY = "SELECT 0 FROM session_nodes WHERE node_id = ? " .
    "AND (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time)";

// Check if any of the specified nodes are already active
foreach ($data["nodes"] as $node)
{
    $result = query_database($db_connection, $QUERY, [$node["nodeId"]]);
    if ($result === FALSE || $result !== NULL) die("false");
}

// Pre-insert checks raised no issues so insert the session
$QUERY = "INSERT INTO sessions (user_id, name, description) VALUES (?, ?, ?)";
$result = query_database($db_connection, $QUERY, [$session["user_id"], $data["name"],
    $data["description"]]);
if ($result === FALSE) die("false");

$new_session_id = $db_connection->lastInsertId();
$QUERY = "INSERT INTO session_nodes (session_id, node_id, location, start_time, end_time, " .
    "`interval`, batch_size) VALUES (?, ?, ?, NOW(), ?, ?, ?)";

// Add the nodes to the newly inserted session
foreach ($data["nodes"] as $node)
{
    $result = query_database($db_connection, $QUERY, [$new_session_id, $node["nodeId"],
        $node["location"], $node["endTime"], $node["interval"], $node["batchSize"]]);
    if ($result === FALSE) die("false");
}

echo "true";