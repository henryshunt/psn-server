<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

function api_projects_get()
{
    global $pdo, $userId;

    // ----- Validation
    $validator = V
        ::key("mode", V::anyOf(V::identical("active"), V::identical("completed")), false);

    try { $validator->check($_GET); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    // ----- Query generation
    $sql = "SELECT projects.projectId, name, description, createdAt, startAt, endAt, nodeCount,
                (nodeCount IS NOT NULL AND (endAt IS NULL OR NOW() < endAt)) as isActive
                    FROM projects LEFT JOIN
                        (SELECT projectId, MIN(startAt) AS startAt, MAX(endAt) AS endAt, COUNT(*) AS nodeCount
                            FROM projectNodes GROUP BY projectId)
                    AS b ON b.projectId = projects.projectId WHERE userId = ?";

    if (isset($_GET["mode"]) && $_GET["mode"] === "active")
        $sql .= " AND nodeCount IS NOT NULL AND (endAt IS NULL OR NOW() < endAt)";
    else if (isset($_GET["mode"]) && $_GET["mode"] === "completed")
        $sql .= " AND nodeCount IS NULL OR (endAt IS NOT NULL AND NOW() >= endAt)";
    
    $sql .= " ORDER BY name";

    // ----- Query execution
    try
    {
        $query = database_query($pdo, $sql, [$userId]);

        if (!(isset($_GET["mode"]) && $_GET["mode"] === "active"))
        {
            for ($i = 0; $i < count($query); $i++)
            {
                $query[$i]["isActive"] = (bool)$query[$i]["isActive"];

                if ($query[$i]["nodeCount"] === null)
                {
                    $query[$i]["nodeCount"] = 0;
                    unset($query[$i]["startAt"]);
                    unset($query[$i]["endAt"]);
                }
            }
        }

        return (new Response(200))->setBody(json_encode($query));
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}

function api_projects_post()
{
    global $pdo, $userId;

    // ----- Validation section
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

    // ----- Query generation section
    $json["userId"] = $userId;

    $sqlColumns = [];
    $values = array_values($json);

    foreach ($json as $key => $value)
        array_push($sqlColumns, "`$key`");

    $sql = "INSERT INTO projects (" . join(", ", $sqlColumns) . ") VALUES (" .
        join(", ", array_fill(0, count($values), "?")) . ")";

    // ----- Query execution section
    try
    {
        global $pdo;
        database_query($pdo, $sql, $values);

        return (new Response(200))->setBody("{\"projectId\":" . $pdo->lastInsertId() . "}");
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