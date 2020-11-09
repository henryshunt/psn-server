<?php
function api_sessions_get()
{
    global $pdo;
    $user_id = api_authenticate($pdo);

    try
    {
        $sql = "SELECT * FROM sessions WHERE user_id = ?";
        $query = database_query($pdo, $sql, [$user_id]);
        return [200, json_encode($query)];
    }
    catch (PDOException $ex)
    {
        return [500, null];
    }
}