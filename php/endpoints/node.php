<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

function api_node_get($nodeId)
{
    global $pdo;

    // ----- Validation
    $validator = V::key("project", V::in(["true", "false"], true), false);

    try { $validator->check($_GET); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    // ----- Query generation
    if (isset($_GET["project"]) && $_GET["project"] === "true")
    {
        $sql = "SELECT nodes.*, projectId b_projectId, location b_location, startAt b_startAt, endAt b_endAt,
                    `interval` b_interval, batchSize b_batchSize, latestReportId b_latestReportId
                FROM nodes
                    LEFT JOIN (SELECT * FROM projectNodes WHERE endAt IS NULL OR NOW() < endAt) b
                        ON b.nodeId = nodes.nodeId WHERE nodes.nodeId = ?";
    }
    else $sql = "SELECT * FROM nodes WHERE nodeId = ?";

    // ----- Query execution
    try
    {
        $query = database_query($pdo, $sql, [$nodeId]);

        if (count($query) === 0)
            return new Response(404);

        if (isset($_GET["project"]) && $_GET["project"] === "true")
        {
            // Move the keys from the projectNodes table into a project sub-object
            foreach ($query[0] as $key => $value)
            {
                if (starts_with($key, "b_"))
                {
                    $query[0]["currentProject"][substr($key, 2)] = $value;
                    unset($query[0][$key]);
                }
            }

            // If no project exists then set the project sub-object to null
            if ($query[0]["currentProject"]["projectId"] === null)
                $query[0]["currentProject"] = null;
        }

        return (new Response(200))->setBody(json_encode($query[0]));
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}

function api_node_mac_get($macAddress)
{
    global $pdo;

    // ----- Validation
    $validator = V::key("project", V::in(["true", "false"], true), false);

    try { $validator->check($_GET); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    // ----- Query generation
    if (isset($_GET["project"]) && $_GET["project"] === "true")
    {
        $sql = "SELECT nodes.*, projectId b_projectId, location b_location, startAt b_startAt, endAt b_endAt,
                `interval` b_interval, batchSize b_batchSize, latestReportId b_latestReportId
                FROM nodes
                    LEFT JOIN (SELECT * FROM projectNodes WHERE endAt IS NULL OR NOW() < endAt) b
                        ON b.nodeId = nodes.nodeId WHERE nodes.macAddress = ?";
    }
    else $sql = "SELECT * FROM nodes WHERE macAddress = ?";

    // ----- Query execution
    try
    {
        $query = database_query($pdo, $sql, [$macAddress]);

        if (count($query) === 0)
            return new Response(404);

        if (isset($_GET["project"]) && $_GET["project"] === "true")
        {
            // Move the keys from the projectNodes table into a project sub-object
            foreach ($query[0] as $key => $value)
            {
                if (starts_with($key, "b_"))
                {
                    $query[0]["currentProject"][substr($key, 2)] = $value;
                    unset($query[0][$key]);
                }
            }

            // If no project exists then set the project sub-object to null
            if ($query[0]["currentProject"]["projectId"] === null)
                $query[0]["currentProject"] = null;
        }

        return (new Response(200))->setBody(json_encode($query[0]));
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}

function api_node_patch($nodeId)
{
    global $pdo;

    // ----- Validation
    $json = json_decode(file_get_contents("php://input"));

    if (gettype($json) !== "object")
        return (new Response(400))->setError("Invalid JSON object supplied");

    $json = (array)$json;

    $validator = V
        ::key("name", V::anyOf(V::nullType(), V::stringType()->length(1, 128)), false);

    try { $validator->check($json); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    $json = filter_attributes_allowed($json, ["name"]);

    if (count($json) === 0)
        return (new Response(400))->setError("No attributes supplied");

    // Check the node exists
    $node = api_node_get($nodeId);

    if ($node->getStatus() !== 200)
        return $node;

    // ----- Query execution
    try
    {
        $sql = "UPDATE nodes SET name = ?";
        database_query($pdo, $sql, $json["name"]);
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

function api_node_delete($nodeId)
{
    global $pdo;

    try
    {
        $sql = "DELETE FROM nodes WHERE nodeId = ?";
        $affected = query_database_affected($pdo, $sql, [$nodeId]);
        return new Response($affected > 0 ? 200 : 404);
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}