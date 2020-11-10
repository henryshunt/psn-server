<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

function api_projects_get()
{
    // ----- Validation
    $validator = V
        ::key("mode", V::anyOf(V::identical("active"), V::identical("completed")), false);

    try { $validator->check($_GET); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    // ----- Query generation
    $sql = "SELECT *, " .
        "(SELECT startAt FROM projectNodes WHERE projectId = projects.projectId ORDER BY startAt ASC LIMIT 1) AS startAt, " .
        "(SELECT endAt FROM projectNodes WHERE projectId = projects.projectId ORDER BY endAt DESC LIMIT 1) AS endAt, " .
        "(SELECT COUNT(*) FROM projectNodes WHERE projectId = projects.projectId) AS nodeCount " .
        "FROM projects WHERE userId = ?";

    if (isset($_GET["mode"]) && $_GET["mode"] === "active")
    {
        $sql .= " HAVING startAt IS NOT NULL " .
            "AND (startAt > NOW() OR endAt IS NULL OR NOW() BETWEEN startAt AND endAt) ORDER BY name";
    }
    else if (isset($_GET["mode"]) && $_GET["mode"] === "completed")
    {
        $sql .= " HAVING NOT (startAt IS NOT NULL " .
            "AND (startAt > NOW() OR endAt IS NULL OR NOW() BETWEEN startAt AND endAt)) ORDER BY name";
    }

    // ----- Query execution
    try
    {
        global $pdo, $userId;
        $query = database_query($pdo, $sql, [$userId]);
        return (new Response(200))->setBody(json_encode($query));
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}

function api_projects_post()
{
    // ----- Validation section
    $json = json_decode(file_get_contents("php://input"));

    if (gettype($json) === "object")
    {
        $json = (array)$json;

        $validator = V
            ::key("name", V::stringType()->length(1, 128))
            ->key("description", V::anyOf(
                V::nullType(), V::stringType()->length(1, 255)), false);

        try { $validator->check($json); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        $json = filter_attributes_allowed($json, ["name", "description"]);
    }
    else return (new Response(400))->setError("Invalid JSON object supplied");

    // ----- Query generation section
    global $userId;
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