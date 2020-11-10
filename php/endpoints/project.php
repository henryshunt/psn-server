<?php

function api_project_get($projectId, $asArray = false)
{
    // ----- Query generation
    $sql = "SELECT *, " .
        "(SELECT startAt FROM projectNodes WHERE projectId = projects.projectId ORDER BY startAt ASC LIMIT 1) AS startAt, " .
        "(SELECT endAt FROM projectNodes WHERE projectId = projects.projectId ORDER BY endAt DESC LIMIT 1) AS endAt, " .
        "(SELECT COUNT(*) FROM projectNodes WHERE projectId = projects.projectId) AS nodeCount " .
        "FROM projects WHERE projectId = ? AND userId = ?";

    // ----- Query execution
    try
    {
        global $pdo, $userId;
        $query = database_query($pdo, $sql, [$projectId, $userId]);

        if (count($query) > 0)
        {
            if (!$asArray)
                return (new Response(200))->setBody(json_encode($query[0]));
            else return (new Response(200))->setBody($query[0]);
        }
        else return new Response(404);
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}