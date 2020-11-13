<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

function api_project_get($projectId)
{
    global $pdo;
    
    try
    {
        $sql = "SELECT projects.projectId, name, description, createdAt, startAt, endAt, nodeCount,
                    (nodeCount IS NOT NULL AND (endAt IS NULL OR NOW() < endAt)) as isActive
                        FROM projects LEFT JOIN
                            (SELECT projectId, MIN(startAt) AS startAt, MAX(endAt) AS endAt, COUNT(*) AS nodeCount
                                FROM projectNodes GROUP BY projectId)
                        AS b ON b.projectId = projects.projectId WHERE projects.projectId = ?";
                
        $query = database_query($pdo, $sql, [$projectId]);

        if (count($query) > 0)
        {
            $query[0]["isActive"] = (bool)$query[0]["isActive"];

            if ($query[0]["nodeCount"] === null)
            {
                $query[0]["nodeCount"] = 0;
                unset($query[0]["startAt"]);
                unset($query[0]["endAt"]);
            }

            return (new Response(200))->setBody(json_encode($query[0]));
        }
        else return new Response(404);
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}

function api_project_patch($projectId)
{
    global $pdo;

    // ----- Validation
    $json = json_decode(file_get_contents("php://input"));

    if (gettype($json) !== "object")
        return (new Response(400))->setError("Invalid JSON object supplied");

    $json = (array)$json;

    $validator = V
        ::key("name", V::stringType()->length(1, 128), false)
        ->key("description", V::anyOf(V::nullType(), V::stringType()->length(1, 255)), false);

    try { $validator->check($json); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    $json = filter_attributes_allowed($json, ["name", "description"]);

    if (count($json) === 0)
        return (new Response(400))->setError("No attributes supplied");

    // Check the project exists
    $project = api_project_get($projectId);

    if ($projectId->getStatus() !== 200)
        return $projectId;

    // ----- Query generation
    $sqlColumns = [];
    $values = array_values($json);

    foreach ($json as $key => $value)
        array_push($sqlColumns, "`$key` = ?");

    $sql = "UPDATE projects SET " . join(", ", $sqlColumns) . " WHERE projectId = ?";
    array_push($values, $projectId);

    // ----- Query execution
    try
    {
        database_query($pdo, $sql, $values);
        return new Response(200);
    }
    catch (PDOException $ex)
    {
        if ($ex->errorInfo[1] === 1062 &&
            strpos($ex->errorInfo[2], "for key 'name'") !== false)
        {
            return (new Response(400))->setError("name is not unique");
        }
        else return new Response(500);
    }
}

function api_project_delete($projectId)
{
    global $pdo;

    try
    {
        $sql = "DELETE FROM projects WHERE projectId = ?";
        $affected = query_database_affected($pdo, $sql, [$projectId]);
        return new Response($affected > 0 ? 200 : 404);
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}