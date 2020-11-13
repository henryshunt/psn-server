<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

function api_nodes_get()
{
    global $pdo;

    // ----- Validation
    $validator = V
        ::key("project", V::in(["true", "false"], true), false)
        ->key("inactive", V::in(["true", "false"], true), false);

    try { $validator->check($_GET); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    // ----- Query generation
    if (isset($_GET["inactive"]) && $_GET["inactive"] === "true")
    {
        $sql = "SELECT * FROM nodes WHERE nodeId NOT IN
                (SELECT nodeId FROM projectNodes WHERE endAt IS NULL OR NOW() < endAt)";
    }
    else if (isset($_GET["project"]) && $_GET["project"] === "true")
    {
        $sql = "SELECT nodes.*, projectId b_projectId, location b_location, startAt b_startAt, endAt b_endAt,
                    `interval` b_interval, batchSize b_batchSize, latestReportId b_latestReportId
                FROM nodes
                    LEFT JOIN (SELECT * FROM projectNodes WHERE endAt IS NULL OR NOW() < endAt) b
                        ON b.nodeId = nodes.nodeId";
    }
    else $sql = "SELECT * FROM nodes";

    $sql .= " ORDER BY macAddress";

    // ----- Query execution
    try
    {
        $query = database_query($pdo, $sql);

        // Ensure each node has a currentProject attribute
        if (isset($_GET["project"]) && $_GET["project"] === "true")
        {
            for ($i = 0; $i < count($query); $i++)
            {
                // If inactive then there aren't any keys to move, so just set to null
                if (isset($_GET["inactive"]) && $_GET["inactive"] === "true")
                    $query[$i]["currentProject"] = null;
                else
                {
                    // Move the keys from the projectNodes table into a currentProject sub-object
                    foreach ($query[$i] as $key => $value)
                    {
                        if (starts_with($key, "b_"))
                        {
                            $query[$i]["currentProject"][substr($key, 2)] = $value;
                            unset($query[$i][$key]);
                        }
                    }

                    if ($query[$i]["currentProject"]["projectId"] === null)
                        $query[$i]["currentProject"] = null;
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

function api_nodes_post()
{
    global $pdo;

    // ----- Validation
    $json = json_decode(file_get_contents("php://input"));

    if (gettype($json) !== "object")
        return (new Response(400))->setError("Invalid JSON object supplied");

    $json = (array)$json;

    $validator = V
        ::key("macAddress", V::stringType()->regex("/([a-fA-F0-9]{2}:){5}[a-fA-F0-9]{2}/"))
        ->key("name", V::anyOf(V::nullType(), V::stringType()->length(1, 128)), false);

    try { $validator->check($json); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    $json = filter_attributes_allowed($json, ["macAddress", "name"]);

    // ----- Query generation
    $sqlColumns = [];
    $values = array_values($json);

    foreach ($json as $key => $value)
        array_push($sqlColumns, "`$key`");

    $sql = "INSERT INTO nodes (" . join(", ", $sqlColumns) . ") VALUES (" .
        join(", ", array_fill(0, count($values), "?")) . ")";

    // ----- Query execution
    try
    {
        database_query($pdo, $sql, $values);
        return (new Response(200))->setBody("{\"nodeId\":" . $pdo->lastInsertId() . "}");
    }
    catch (PDOException $ex)
    {
        if ($ex->errorInfo[1] === 1062 &&
            strpos($ex->errorInfo[2], "for key 'macAddress'") !== false)
        {
            return (new Response(400))->setError("macAddress is not unique");
        }
        else if ($ex->errorInfo[1] === 1062 &&
            strpos($ex->errorInfo[2], "for key 'name'") !== false)
        {
            return (new Response(400))->setError("name is not unique");
        }
        else return new Response(500);
    }
}