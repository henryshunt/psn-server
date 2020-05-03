<?php
/**
 * Returns a downloadable ZIP file of CSV files containing all reports from each node
 * in a session
 */

require_once("../resources/routines/helpers.php");
require_once("../resources/routines/config.php");

if (!isset($_GET["sessionId"])) die("false");
$config = new Config();
if (!$config->load_config("../config.ini"))
    die("false");
$db_connection = database_connection($config);
if (!$db_connection) die("false");

$session = try_loading_session($db_connection);
if ($session === FALSE || $session === NULL)
    die("false");

    
if (!file_exists("../output")) mkdir("../output");
if (file_exists("../output/psn_data.zip"))
    unlink("../output/psn_data.zip");

// Get the nodes in the session
$QUERY = "SELECT node_id, location FROM session_nodes WHERE session_id = ?";
$result = query_database($db_connection, $QUERY, [$_GET["sessionId"]]);
if ($result === FALSE) die("false");

$zip_file = new ZipArchive;
if ($zip_file->open("../output/psn_data.zip", ZipArchive::CREATE))
{
    // Create a CSV file in the ZIP for each node in the session
    foreach ($result as $node)
    {
        $QUERY = "SELECT time, airt, relh, batv FROM reports WHERE session_id = ? AND node_id = ?";
        $result2 = query_database($db_connection, $QUERY, [$_GET["sessionId"], $node["node_id"]]);
        
        if ($result2 === FALSE)
        {
            $zip_file->close();
            die("false");
        }

        $csv_output = fopen("php://memory", "w");
        fputcsv($csv_output, array("time", "airt", "relh", "batv"));

        if ($result2 !== NULL)
        {
            // Output retrieved reports in CSV format
            foreach ($result2 as $report)
                fputcsv($csv_output, $report);
        }
        
        rewind($csv_output);
        $file_name = preg_replace("([^a-zA-Z0-9 '-])", "", $node["location"]); // Make safe file name
        $zip_file->addFromString($file_name . ".csv", stream_get_contents($csv_output));
        fclose($csv_output);
    }

    $zip_file->close();
} else die("false");

// Make this PHP file respond with a ZIP file download
header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=psn_data.zip");
header("Content-length: " . filesize("../output/psn_data.zip"));
readfile("../output/psn_data.zip");