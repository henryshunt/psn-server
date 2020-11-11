<?php
function api_project_node_get($projectId, $nodeId)
{
    try
    {
        global $pdo;
        $sql = "SELECT * FROM projectNodes WHERE projectId = ? AND nodeId = ?";
        $query = database_query($pdo, $sql, [$projectId, $nodeId]);

        if (count($query) > 0)
            return (new Response(200))->setBody(json_encode($query[0]));
        else return new Response(404);
    }
    catch (PDOException $ex)
    {
        return (new Response(500))->setError($ex->getMessage());
    }
}