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