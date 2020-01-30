<?php
date_default_timezone_set("UTC");
include_once("helpers.php");
include_once("config.php");

$session_info_html = "";
$active_nodes_html = "";
$completed_nodes_html = "";

main();


function main()
{
    global $ERROR_ITEM_HTML, $EMPTY_ITEM_HTML, $session_info_html, $active_nodes_html,
        $completed_nodes_html;

    // Perform various setup checks before continuing
    $setup_error = false;
    if (!isset($_GET["id"])) $setup_error = true;
    $config = new Config();
    if (!$config->load_config("config.ini")) $setup_error = true;
    $db_connection = database_connection($config);
    if (!$db_connection) $setup_error = true;

    if ($setup_error)
    {
        $session_info_html = $ERROR_ITEM_HTML;
        $active_nodes_html = $ERROR_ITEM_HTML;
        $completed_nodes_html = $ERROR_ITEM_HTML;
        return;
    }


    // Get session info
    $session_info = get_session_info_html($db_connection);

    if ($session_info === false)
        $session_info_html = $ERROR_ITEM_HTML;
    else if ($session_info === NULL)
        $session_info_html = $EMPTY_ITEM_HTML;
    else $session_info_html = $session_info;

    // Get active nodes
    $active_nodes = get_active_nodes_html($db_connection);
    
    if ($active_nodes === false)
        $active_nodes_html = $ERROR_ITEM_HTML;
    else if ($active_nodes === NULL)
        $active_nodes_html = $EMPTY_ITEM_HTML;
    else $active_nodes_html = $active_nodes;

    // Get completed nodes
    $completed_nodes = get_completed_nodes_html($db_connection);
    
    if ($completed_nodes === false)
        $completed_nodes_html = $ERROR_ITEM_HTML;
    else if ($completed_nodes === NULL)
        $completed_nodes_html = $EMPTY_ITEM_HTML;
    else $completed_nodes_html = $completed_nodes;
}


function get_session_info_html($db_connection)
{
    $QUERY = "SELECT name, description FROM sessions WHERE session_id = ?";
    $result = query_database($db_connection, $QUERY, [$_GET["id"]]);
    if ($result === false || $result === NULL) return $result;

    $html = "<div class=\"session_info\">";

    // Left column
    $html .= "<div class=\"session_info_left\">";
    $html .= "<span>" . $result[0]["name"] . "</span>";
    $html .= "<br>";
    
    if ($result[0]["description"] === NULL)
        $html .= "<span>No description available</span>";
    else $html .= "<span>" . $result[0]["description"] . "</span>";

    $html .= "</div>";

    // Right column
    $html .= "<div class=\"session_info_right\">";
    $html .= "<div class=\"button\">Stop Session Now</div>";
    $html .= "<div class=\"button\">Download All Data</div>";
    $html .= "<div class=\"button\">Delete Session</div>";
    $html .= "</div>";

    $html .= "</div>";
    return $html;
}

function get_active_nodes_html($db_connection)
{
    $QUERY = "SELECT node_id, location, " .
        "(SELECT report_id FROM reports WHERE session_id = session_nodes.session_id AND node_id = session_nodes.node_id ORDER BY time " .
        "DESC LIMIT 1) AS latest_report FROM session_nodes WHERE session_id = ? " .
        "AND (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time) ORDER BY location";

    $result = query_database($db_connection, $QUERY, [$_GET["id"]]);
    if ($result === false || $result === NULL) return $result;

    $html = "";
    foreach ($result as $session)
    {
        $html .= "<div class=\"group_item\">";
        $html .= "<div>";
        $html .= "<span>" . $session["location"] . "</span>";
        $html .= "<br>";
        
        // Latest report time
        $html .= "<span>Latest Report: ";
        if ($session["latest_report"] !== NULL)
        {
            $QUERY = "SELECT time, airt, relh, batv FROM reports WHERE report_id = ?";
            $result_report = query_database($db_connection, $QUERY, [$result[0]["latest_report"]]);
            if ($result_report === false || $result_report === NULL) return false;
            
            $report_time = DateTime::createFromFormat("Y-m-d H:i:s", $result_report[0]["time"]);
            $html .= $report_time->format("d/m/Y \a\\t H:i");
        }
        else $html .= "None";
        $html .= "</span>";

        // Latest report data
        if ($session["latest_report"] !== NULL)
        {
            $html .= "<div class=\"group_item_data\">";

            $html .= "<div><span>Temp.</span><br><span>";
            if ($result_report[0]["airt"] !== NULL)
                $html .= round($result_report[0]["airt"], 1) . "°C";
            else $html .= "None";
            $html .= "</span></div>";

            $html .= "<div><span>Humid.</span><br><span>";
            if ($result_report[0]["relh"] !== NULL)
                $html .= round($result_report[0]["relh"], 1) . "%";
            else $html .= "None";
            $html .= "</span></div>";

            $html .= "<div><span>Battery</span><br><span>";
            if ($result_report[0]["batv"] !== NULL)
                $html .= round($result_report[0]["batv"], 1) . "V";
            else $html .= "None";
            $html .= "</span></div>";

            $html .= "</div>";
        }

        $html .= "</div>";
        $html .= "</div>";
    }

    return $html;
}

function get_completed_nodes_html($db_connection)
{
    $QUERY = "SELECT node_id, location, " .
        "(SELECT report_id FROM reports WHERE session_id = session_nodes.session_id AND node_id = session_nodes.node_id ORDER BY time " .
        "DESC LIMIT 1) AS latest_report FROM session_nodes WHERE session_id = ? " .
        "AND NOT (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time) ORDER BY location";

    $result = query_database($db_connection, $QUERY, [$_GET["id"]]);
    if ($result === false || $result === NULL) return $result;
    
    $html = "";
    foreach ($result as $session)
    {
        $html .= "<div class=\"group_item\">";
        $html .= "<div>";
        $html .= "<span>" . $session["location"] . "</span>";
        $html .= "<br>";
        
        // Latest report time
        $html .= "<span>Latest Report: ";
        if ($session["latest_report"] !== NULL)
        {
            $QUERY = "SELECT time, airt, relh, batv FROM reports WHERE report_id = ?";
            $result_report = query_database($db_connection, $QUERY, [$result[0]["latest_report"]]);
            if ($result_report === false || $result_report === NULL) return false;
            
            $report_time = DateTime::createFromFormat("Y-m-d H:i:s", $result_report[0]["time"]);
            $html .= $report_time->format("d/m/Y \a\\t H:i");
        }
        else $html .= "None";
        $html .= "</span>";

        $html .= "</div>";
        $html .= "</div>";
    }

    return $html;
}