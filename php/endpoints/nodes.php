<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

function api_nodes_get()
{
    $sql = "SELECT * FROM nodes";

    if ($_GET["completed-only"] === "true")
    {
        $sql .=  " WHERE node_id NOT IN (SELECT node_id FROM session_nodes " .
            "WHERE (start_time > NOW() OR end_time IS NULL OR NOW() BETWEEN start_time AND end_time))";
    }

    try
    {
        global $pdo;
        $query = database_query($pdo, $sql, null);
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

        $validator = V::key("mac_address", V::stringType()->length(17));

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
        $sql = "INSERT INTO nodes (mac_address) VALUES (?)";
        database_query($pdo, $sql, [$json["mac_address"]]);

        return (new Response(200))->setBody("{\"node_id\":" . $pdo->lastInsertId() . "}");
    }
    catch (PDOException $ex)
    {
        if ($ex->errorInfo[1] === 1062 &&
            strpos($ex->errorInfo[2], "for key 'mac_address'") !== false)
        {
            return (new Response(409))->setError("mac_address is not unique");
        }
        else return new Response(500);
    }
}