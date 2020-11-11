<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

function api_node_get($nodeId)
{
    // ----- Validation
    $validator = V
        ::key("project", V::anyOf(V::identical("true"), V::identical("false")), false);

    try { $validator->check($_GET); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    // ----- Query generation
    if (isset($_GET["project"]) && $_GET["project"] === "true")
    {
        $sql = "SELECT *, (SELECT projectId FROM projectNodes WHERE nodeId = ? " .
            "AND NOW() >= startAt AND (endAt IS NULL OR NOW() < endAt)) AS projectId FROM nodes WHERE nodeId = ?";
        $values = [$nodeId, $nodeId];
    }
    else
    {
        $sql = "SELECT * FROM nodes WHERE nodeId = ?";
        $values = [$nodeId];
    }

    // ----- Query execution
    try
    {
        global $pdo;
        $query = database_query($pdo, $sql, $values);

        if (count($query) > 0)
            return (new Response(200))->setBody(json_encode($query[0]));
        else return new Response(404);
    }
    catch (PDOException $ex)
    {
        return (new Response(500))->setError($ex->getMessage());
    }
}

function api_node_mac_get($macAddress)
{
    // ----- Validation
    $validator = V
        ::key("project", V::anyOf(V::identical("true"), V::identical("false")), false);

    try { $validator->check($_GET); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    // ----- Query generation
    if (isset($_GET["project"]) && $_GET["project"] === "true")
    { 
        $sql = "SELECT *, (SELECT projectId FROM projectNodes WHERE nodeId = (SELECT nodeId FROM nodes WHERE macAddress = ?) " .
            "AND NOW() >= startAt AND (endAt IS NULL OR NOW() < endAt)) AS projectId FROM nodes WHERE nodeId = (SELECT nodeId FROM nodes WHERE macAddress = ?)";
        $values = [$macAddress, $macAddress];
    }
    else
    {
        $sql = "SELECT * FROM nodes WHERE macAddress = ?";
        $values = [$macAddress];
    }

    // ----- Query execution
    try
    {
        global $pdo;
        $query = database_query($pdo, $sql, $values);

        if (count($query) > 0)
        {
            if ($query[0]["projectId"] !== null)
            {
                $sql = "SELECT * FROM projectNodes WHERE projectId = ? AND nodeId = ?";
                $query2 = database_query($pdo, $sql, [$query[0]["projectId"], $query[0]["nodeId"]]);

                if (count($query2) > 0)
                    $query[0]["project"] = $query2[0];
                else $query[0]["project"] = null;
            }
            else $query[0]["project"] = null;

            unset($query[0]["projectId"]);
            return (new Response(200))->setBody(json_encode($query[0]));
        }
        else return new Response(404);
    }
    catch (PDOException $ex)
    {
        return (new Response(500))->setError($ex->getMessage());
    }
}