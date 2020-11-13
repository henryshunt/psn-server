<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

function api_project_get($projectId, $asArray = false)
{
    global $pdo;
    
    try
    {
        $sql = "SELECT name, description, createdAt, startAt, endAt, nodeCount,
                    (nodeCount IS NOT NULL AND (endAt IS NULL OR NOW() < endAt)) isActive
                FROM projects
                    LEFT JOIN
                        (SELECT projectId, MIN(startAt) startAt, MAX(endAt) endAt, COUNT(*) nodeCount
                            FROM projectNodes GROUP BY projectId) b
                    ON b.projectId = projects.projectId WHERE projects.projectId = ?";
                
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

function api_project_patch($projectId)
{
    global $pdo;

    // ----- Validation 1
    $validator = V::key("stop", V::in(["true", "false"], true), false);

    try { $validator->check($_GET); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    // Check the project exists
    $project = api_project_get($projectId, true);

    if ($project->getStatus() !== 200)
        return $project;

    // If stop=true then do something different
    if (isset($_GET["stop"]) && $_GET["stop"] === "true")
        return api_project_stop($projectId, $project);

    // ----- Validation 2
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
            strpos($ex->errorInfo[2], "for key 'userId_name'") !== false)
        {
            return (new Response(400))->setError("name is not unique within user");
        }
        else return new Response(500);
    }
}

function api_project_stop($projectId, $project)
{
    global $pdo;

    if (!$project["isActive"])
        return (new Response(400))->setError("Project is not active");

    try
    {
        $sql = "UPDATE projectNodes SET (endAt = NOW())
                    WHERE projectId = ? AND (endAt IS NULL OR NOW() < endAt)";

        $query = database_query($pdo, $sql, [$projectId]);
        return new Response(200);
    }
    catch (PDOException $ex)
    {
        return new Response(500);
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