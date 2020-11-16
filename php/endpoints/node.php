<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

function endp_node_get($nodeId)
{
    $validator = V::key("project", V::in(["true", "false"], true), false);

    try { $validator->check($_GET); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    return endpmain_node_get($nodeId);
}

function endpmain_node_get($nodeId)
{
    global $pdo;

    // ----- Query generation
    $sql = "SELECT nodes.macAddress, nodes.name, nodes.createdAt";

    if (isset($_GET["project"]) && $_GET["project"] === "true")
    {
        $sql .= ", projectId b_projectId, location b_location, startAt b_startAt, endAt b_endAt,
                    `interval` b_interval, batchSize b_batchSize, latestReportId b_latestReportId
                FROM nodes
                    LEFT JOIN (SELECT * FROM projectNodes WHERE endAt IS NULL OR NOW() < endAt) b
                        ON b.nodeId = nodes.nodeId WHERE nodes.nodeId = ?";
    }
    else $sql .= " FROM nodes WHERE nodeId = ?";

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

            if ($query[0]["currentProject"]["projectId"] === null)
                $query[0]["currentProject"] = null;
        }

        return (new Response(200))->setBody($query[0]);
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}


function endp_node_mac_get($macAddress)
{
    $validator = V::key("project", V::in(["true", "false"], true), false);

    try { $validator->check($_GET); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    return endpmain_node_mac_get($macAddress);
}

function endpmain_node_mac_get($macAddress)
{
    global $pdo;

    // ----- Query generation
    $sql = "SELECT nodes.macAddress, nodes.name, nodes.createdAt";

    if (isset($_GET["project"]) && $_GET["project"] === "true")
    {
        $sql .= ", projectId b_projectId, location b_location, startAt b_startAt, endAt b_endAt,
                    `interval` b_interval, batchSize b_batchSize, latestReportId b_latestReportId
                FROM nodes
                    LEFT JOIN (SELECT * FROM projectNodes WHERE endAt IS NULL OR NOW() < endAt) b
                        ON b.nodeId = nodes.nodeId WHERE nodes.macAddress = ?";
    }
    else $sql .= " FROM nodes WHERE macAddress = ?";

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

        return (new Response(200))->setBody($query[0]);
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}


function endp_node_patch($nodeId)
{
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

    $json = filter_attributes_allowed($json, ["name"]));

    if (count($json) === 0)
        return (new Response(400))->setError("No attributes supplied");

    // Check the node exists
    $node = endp_node_get($nodeId);

    if ($node->getStatus() !== 200)
        return $node;

    return endpmain_node_patch($nodeId, $json);
}

function endpmain_node_patch($nodeId, $json)
{
    global $pdo;

    try
    {
        $sql = "UPDATE nodes SET name = ? WHERE nodeId = ?";
        database_query($pdo, $sql, [$json["name"], $nodeId]);
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


function endp_node_delete($nodeId)
{
    return endpmain_node_delete($nodeId);
}

function endpmain_node_delete($nodeId)
{
    global $pdo;

    try
    {
        $sql = "DELETE FROM nodes WHERE nodeId = ?";
        $affected = database_query_affected($pdo, $sql, [$nodeId]);
        return new Response($affected > 0 ? 200 : 404);
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}