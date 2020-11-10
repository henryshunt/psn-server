<?php
function api_sessions_get()
{
    $sql = "SELECT *, " .
        "(SELECT start_time FROM session_nodes WHERE session_id = sessions.session_id ORDER BY start_time ASC LIMIT 1) AS start_time, " .
        "(SELECT end_time FROM session_nodes WHERE session_id = sessions.session_id ORDER BY end_time DESC LIMIT 1) AS end_time, " .
        "(SELECT COUNT(*) FROM session_nodes WHERE session_id = sessions.session_id) AS node_count " .
        "FROM sessions WHERE user_id = ?";

    if ($_GET["mode"] === "active")
    {
        $sql .= " HAVING start_time IS NOT NULL " .
            "AND (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time) ORDER BY name";
    }
    else if ($_GET["mode"] === "completed")
    {
        $sql .= " HAVING NOT (start_time IS NOT NULL " .
            "AND (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time)) ORDER BY name";
    }

    try
    {
        global $pdo, $user_id;
        $query = database_query($pdo, $sql, [$user_id]);
        return (new Response(200))->setBody(json_encode($query));
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}