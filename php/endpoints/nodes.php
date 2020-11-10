<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

function api_nodes_get()
{
    // ----- Validation
    $validator = V
        ::key("inactive", V::anyOf(V::identical("true"), V::identical("false")), false);

    try { $validator->check($_GET); }
    catch (ValidationException $ex)
    {
        return (new Response(400))->setError($ex->getMessage());
    }

    // ----- Query generation
    if (isset($_GET["inactive"]) && $_GET["inactive"] === "true")
    {
        $sql .=  "SELECT * FROM nodes WHERE nodeId NOT IN (SELECT nodeId FROM projectNodes " .
            "WHERE (startAt > NOW() OR endAt IS NULL OR NOW() BETWEEN startAt AND endAt))";
    }
    else $sql = "SELECT * FROM nodes";

    // ----- Query execution
    try
    {
        global $pdo;
        $query = database_query($pdo, $sql);
        return (new Response(200))->setBody(json_encode($query));
    }
    catch (PDOException $ex)
    {
        return new Response(500);
    }
}

function api_nodes_post()
{
    // ----- Validation
    $json = json_decode(file_get_contents("php://input"));

    if (gettype($json) === "object")
    {
        $json = (array)$json;

        $validator = V::key("macAddress", V::stringType()->length(17));

        try { $validator->check($json); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }
    }
    else return (new Response(400))->setError("Invalid JSON object supplied");

    // ----- Query execution
    try
    {
        global $pdo;
        $sql = "INSERT INTO nodes (macAddress) VALUES (?)";
        database_query($pdo, $sql, [$json["macAddress"]]);

        return (new Response(200))->setBody("{\"nodeId\":" . $pdo->lastInsertId() . "}");
    }
    catch (PDOException $ex)
    {
        if ($ex->errorInfo[1] === 1062 &&
            strpos($ex->errorInfo[2], "for key 'macAddress'") !== false)
        {
            return (new Response(409))->setError("macAddress is not unique");
        }
        else return new Response(500);
    }
}