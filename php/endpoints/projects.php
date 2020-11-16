<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

function endp_projects_get()
{
    $validator = V::key("mode", V::in(["active", "completed"], true), false);

    try { $validator->check($_GET); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    return endpmain_projects_get();
}

function endpmain_projects_get()
{
    global $pdo, $userId;

    // ----- Query generation
    $sql = "SELECT projects.projectId, name, description, createdAt, startAt, endAt, nodeCount";

    if (!isset($_GET["mode"]))
        $sql .= ", (nodeCount IS NOT NULL AND (endAt IS NULL OR NOW() < endAt)) isActive";

    $sql .= " FROM projects
                  LEFT JOIN (SELECT projectId, MIN(startAt) startAt, MAX(endAt) endAt, COUNT(*) nodeCount
                      FROM projectNodes GROUP BY projectId) b
                  ON b.projectId = projects.projectId WHERE userId = ?";

    if (isset($_GET["mode"]) && $_GET["mode"] === "active")
        $sql .= " AND nodeCount IS NOT NULL AND (endAt IS NULL OR NOW() < endAt)";
    else if (isset($_GET["mode"]) && $_GET["mode"] === "completed")
        $sql .= " AND nodeCount IS NULL OR (endAt IS NOT NULL AND NOW() >= endAt)";
    
    $sql .= " ORDER BY name";

    // ----- Query execution
    try
    {
        $query = database_query($pdo, $sql, [$userId]);

        // If we're not getting the active projects (which always have at least 1 node)
        // then perform some cleanup of the data
        if (!(isset($_GET["mode"]) && $_GET["mode"] === "active"))
        {
            for ($i = 0; $i < count($query); $i++)
            {
                // If we're not also getting the completed projects...
                if (!isset($_GET["mode"]))
                    $query[$i]["isActive"] = (bool)$query[$i]["isActive"];

                if ($query[$i]["nodeCount"] === null)
                {
                    $query[$i]["nodeCount"] = 0;
                    unset($query[$i]["startAt"]);
                    unset($query[$i]["endAt"]);
                }
            }
        }

        return (new Response(200))->setBody($query);
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}


function endp_projects_post()
{
    $json = json_decode(file_get_contents("php://input"));

    if (gettype($json) !== "object")
        return (new Response(400))->setError("Invalid JSON object supplied");

    $json = (array)$json;

    $validator = V
        ::key("name", V::stringType()->length(1, 128))
        ->key("description", V::anyOf(V::nullType(), V::stringType()->length(1, 255)), false);

    try { $validator->check($json); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    $json = filter_attributes_allowed($json, ["name", "description"]);

    return endpmain_projects_post($json);
}

function api_projects_post($json)
{
    global $pdo, $userId;

    // ----- Query generation
    $json["userId"] = $userId;

    $sqlColumns = [];
    $values = array_values($json);

    foreach ($json as $key => $value)
        array_push($sqlColumns, "`$key`");

    $sql = "INSERT INTO projects (" . join(", ", $sqlColumns) . ") VALUES (" .
        join(", ", array_fill(0, count($values), "?")) . ")";

    // ----- Query execution
    try
    {
        database_query($pdo, $sql, $values);
        return (new Response(200))->setBody(["projectId" => $pdo->lastInsertId()]);
    }
    catch (PDOException $ex)
    {
        if ($ex->errorInfo[1] === 1062 &&
            strpos($ex->errorInfo[2], "for key 'name'") !== false)
        {
            return (new Response(400))->setError("name is not unique within user");
        }
        else return new Response(500);
    }
}