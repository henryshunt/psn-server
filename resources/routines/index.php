<?php
include_once("helpers.php");
include_once("config.php");

$active_sessions_html = "";
$completed_sessions_html = "";
$active_alarms_html = "";

main();


function main()
{
    global $active_sessions_html, $completed_sessions_html, $active_alarms_html;

    $error_item_html = "<div class=\"message_item\"><span>Error Getting Data</span></div>";
    $empty_item_html = "<div class=\"message_item\"><span>Nothing Here</span></div>";

    // Load the user-supplied configuration file
    $config = new Config();
    if (!$config->load_config("config.ini"))
    {
        $active_sessions_html = $error_item_html;
        $completed_sessions_html = $error_item_html;
        $active_alarms_html = $error_item_html;
        return;
    }

    // Connect to the database
    $db_connection = database_connection($config);
    if (!$db_connection)
    {
        $active_sessions_html = $error_item_html;
        $completed_sessions_html = $error_item_html;
        $active_alarms_html = $error_item_html;
        return;
    }


    // Get active sessions
    $active_sessions = get_active_sessions_html($db_connection);
    
    if ($active_sessions === false)
        $active_sessions_html = $error_item_html;
    else if ($active_sessions === NULL)
        $active_sessions_html = $empty_item_html;
    else $active_sessions_html = $active_sessions;

    // Get completed sessions
    $completed_sessions = get_completed_sessions_html($db_connection);
    
    if ($completed_sessions === false)
        $completed_sessions_html = $error_item_html;
    else if ($completed_sessions === NULL)
        $completed_sessions_html = $empty_item_html;
    else $completed_sessions_html = $completed_sessions;

    // Get active alarms
    $active_alarms = get_active_alarms_html($db_connection);
    
    if ($active_alarms === false)
        $active_alarms_html = $error_item_html;
    else if ($active_alarms === NULL)
        $active_alarms_html = $empty_item_html;
    else $active_alarms_html = $active_alarms;
}


function get_active_sessions_html($db_connection)
{
    $QUERY = "SELECT session_id, title, " .
        "(SELECT COUNT(*) FROM session_nodes WHERE session_id = sessions.session_id) AS node_count, " .
        "(SELECT start_time FROM session_nodes WHERE session_id = sessions.session_id ORDER BY start_time ASC LIMIT 1) AS start_time, " .
        "(SELECT end_time FROM session_nodes WHERE session_id = sessions.session_id ORDER BY end_time DESC LIMIT 1) AS end_time " .
        "FROM sessions HAVING start_time IS NOT NULL AND (end_time IS NULL OR NOW() BETWEEN start_time AND end_time) ORDER BY title";

    $result = query_database($db_connection, $QUERY, NULL);
    if ($result === false || $result === NULL) return $result;

    $active_sessions_html = "";

    // Create HTML for the returned session records
    foreach ($result as $session)
    {
        $active_sessions_html .= "<div class=\"session_item\">";
        $active_sessions_html .= "<a href=\"session.php?id=" . $session["session_id"] . "\">";
        $active_sessions_html .= "<span>" . $session["title"] . "</span>";
        $active_sessions_html .= "<br>";
        
        $active_sessions_html .= "<span>From ";
        $start_time = DateTime::createFromFormat("Y-m-d H:i:s", $session["start_time"]);
        $active_sessions_html .= $start_time->format("d/m/Y");

        if ($session["end_time"] !== NULL)
        {
            $end_time = DateTime::createFromFormat("Y-m-d H:i:s", $session["end_time"]);
            $active_sessions_html .= " to " . $end_time->format("d/m/Y");
        }
        
        $active_sessions_html .= "</span>";
        $active_sessions_html .= "<span>" . $session["node_count"] . " Nodes</span>";
        $active_sessions_html .= "</a>";
        $active_sessions_html .= "</div>";
    }

    return $active_sessions_html;
}

function get_completed_sessions_html($db_connection)
{
    $QUERY = "SELECT session_id, title, " .
        "(SELECT COUNT(*) FROM session_nodes WHERE session_id = sessions.session_id) AS node_count, " .
        "(SELECT start_time FROM session_nodes " .
        "WHERE session_id = sessions.session_id ORDER BY start_time ASC LIMIT 1) AS start_time, " .
        "(SELECT end_time FROM session_nodes " .
        "WHERE session_id = sessions.session_id ORDER BY end_time DESC LIMIT 1) AS end_time " .
        "FROM sessions HAVING start_time IS NOT NULL " .
        "AND (end_time IS NOT NULL AND NOW() NOT BETWEEN start_time AND end_time) ORDER BY title";

    $result = query_database($db_connection, $QUERY, NULL);
    if ($result === false || $result === NULL) return $result;
    
    $completed_sessions_html = "";

    // Create HTML for the returned session records
    foreach ($result as $session)
    {
        $completed_sessions_html .= "<div class=\"session_item\">";
        $completed_sessions_html .= "<a href=\"session.php?id=" . $session["session_id"] . "\">";
        $completed_sessions_html .= "<span>" . $session["title"] . "</span>";
        $completed_sessions_html .= "<br>";
        
        $completed_sessions_html .= "<span>From ";
        $start_time = DateTime::createFromFormat("Y-m-d H:i:s", $session["start_time"]);
        $completed_sessions_html .= $start_time->format("d/m/Y");

        if ($session["end_time"] !== NULL)
        {
            $end_time = DateTime::createFromFormat("Y-m-d H:i:s", $session["end_time"]);
            $completed_sessions_html .= " to " . $end_time->format("d/m/Y");
        }
        
        $completed_sessions_html .= "</span>";
        $completed_sessions_html .= "<span>" . $session["node_count"] . " Nodes</span>";
        $completed_sessions_html .= "</a>";
        $completed_sessions_html .= "</div>";
    }

    return $completed_sessions_html;
}

function get_active_alarms_html($db_connection)
{
    return NULL;
}