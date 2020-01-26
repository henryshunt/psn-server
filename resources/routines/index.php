<?php
date_default_timezone_set("UTC");
include_once("helpers.php");
include_once("config.php");

$active_sessions_html = "";
$completed_sessions_html = "";
$active_alarms_html = "";

main();


function main()
{
    global $ERROR_ITEM_HTML, $EMPTY_ITEM_HTML, $active_sessions_html, $completed_sessions_html,
        $active_alarms_html;

    // Perform various setup checks before continuing
    $setup_error = false;
    $config = new Config();
    if (!$config->load_config("config.ini")) $setup_error = true;
    $db_connection = database_connection($config);
    if (!$db_connection) $setup_error = true;

    if ($setup_error)
    {
        $active_sessions_html = $ERROR_ITEM_HTML;
        $completed_sessions_html = $ERROR_ITEM_HTML;
        $active_alarms_html = $ERROR_ITEM_HTML;
        return;
    }


    // Get active sessions
    $active_sessions = get_active_sessions_html($db_connection);
    
    if ($active_sessions === false)
        $active_sessions_html = $ERROR_ITEM_HTML;
    else if ($active_sessions === NULL)
        $active_sessions_html = $EMPTY_ITEM_HTML;
    else $active_sessions_html = $active_sessions;

    // Get completed sessions
    $completed_sessions = get_completed_sessions_html($db_connection);
    
    if ($completed_sessions === false)
        $completed_sessions_html = $ERROR_ITEM_HTML;
    else if ($completed_sessions === NULL)
        $completed_sessions_html = $EMPTY_ITEM_HTML;
    else $completed_sessions_html = $completed_sessions;

    // Get active alarms
    $active_alarms = get_active_alarms_html($db_connection);
    
    if ($active_alarms === false)
        $active_alarms_html = $ERROR_ITEM_HTML;
    else if ($active_alarms === NULL)
        $active_alarms_html = $EMPTY_ITEM_HTML;
    else $active_alarms_html = $active_alarms;
}


function get_active_sessions_html($db_connection)
{
    $QUERY = "SELECT session_id, name, " .
        "(SELECT COUNT(*) FROM session_nodes WHERE session_id = sessions.session_id) AS node_count, " .
        "(SELECT start_time FROM session_nodes WHERE session_id = sessions.session_id ORDER BY start_time ASC LIMIT 1) AS start_time, " .
        "(SELECT end_time FROM session_nodes WHERE session_id = sessions.session_id ORDER BY end_time DESC LIMIT 1) AS end_time " .
        "FROM sessions HAVING start_time IS NOT NULL " .
        "AND (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time) ORDER BY name";

    $result = query_database($db_connection, $QUERY, NULL);
    if ($result === false || $result === NULL) return $result;

    $html = "";
    foreach ($result as $session)
    {
        $html .= "<div class=\"group_item\">";
        $html .= "<a href=\"session.php?id=" . $session["session_id"] . "\">";
        $html .= "<span>" . $session["name"] . "</span>";
        $html .= "<br>";

        // Session start and end time
        $html .= "<span>From ";
        $start_time = DateTime::createFromFormat("Y-m-d H:i:s", $session["start_time"]);
        $html .= $start_time->format("d/m/Y");

        if ($session["end_time"] !== NULL)
        {
            $end_time = DateTime::createFromFormat("Y-m-d H:i:s", $session["end_time"]);
            $html .= " to " . $end_time->format("d/m/Y");
        }
        else $html .= ", indefinitely";
        $html .= "</span>";

        // Sensor node count
        if ($session["node_count"] === 1)
            $html .= "<span>1 Sensor Node</span>";
        else $html .= "<span>" . $session["node_count"] . " Sensor Nodes</span>";

        $html .= "</a>";
        $html .= "</div>";
    }

    return $html;
}

function get_completed_sessions_html($db_connection)
{
    $QUERY = "SELECT session_id, name, " .
        "(SELECT COUNT(*) FROM session_nodes WHERE session_id = sessions.session_id) AS node_count, " .
        "(SELECT start_time FROM session_nodes WHERE session_id = sessions.session_id ORDER BY start_time ASC LIMIT 1) AS start_time, " .
        "(SELECT end_time FROM session_nodes WHERE session_id = sessions.session_id ORDER BY end_time DESC LIMIT 1) AS end_time " .
        "FROM sessions HAVING NOT (start_time IS NOT NULL " .
        "AND (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time)) ORDER BY name";

    $result = query_database($db_connection, $QUERY, NULL);
    if ($result === false || $result === NULL) return $result;
    
    $html = "";
    foreach ($result as $session)
    {
        $html .= "<div class=\"group_item\">";
        $html .= "<a href=\"session.php?id=" . $session["session_id"] . "\">";
        $html .= "<span>" . $session["name"] . "</span>";
        $html .= "<br>";

        // Session start and end time
        if ($session["start_time"] !== NULL)
        {
            $html .= "<span>From ";
            $start_time = DateTime::createFromFormat("Y-m-d H:i:s", $session["start_time"]);
            $html .= $start_time->format("d/m/Y");
            $end_time = DateTime::createFromFormat("Y-m-d H:i:s", $session["end_time"]);
            $html .= " to " . $end_time->format("d/m/Y");
            $html .= "</span>";
        }
        
        // Sensor node count
        if ($session["node_count"] === 1)
            $html .= "<span>1 Sensor Node</span>";
        else $html .= "<span>" . $session["node_count"] . " Sensor Nodes</span>";
        
        $html .= "</a>";
        $html .= "</div>";
    }

    return $html;
}

function get_active_alarms_html($db_connection)
{
    return NULL;
}