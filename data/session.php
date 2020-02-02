<?php
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