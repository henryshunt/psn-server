<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointNodesPost extends Endpoint
{
    private $jsonParams;

    public function response(array $resParams) : Response
    {
        $this->resParams = $resParams;

        if (!$this->user["privNodes"])
            return (new Response(403))->setBody("Only privileged users can create nodes");

        $loadJson = $this->loadJsonParams();
        if ($loadJson->getStatus() !== 200)
            return $loadJson;

        $validation = $this->validateParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        return $this->createNode();
    }

    public function loadJsonParams() : Response
    {
        $json = json_decode(file_get_contents("php://input"));

        if (gettype($json) !== "object")
            return (new Response(400))->setError("Invalid JSON object supplied");

        $json = (array)$json;
        $json = filter_keys($json, ["macAddress", "name"]);

        $this->jsonParams = $json;
        return new Response(200);
    }

    private function validateParams() : Response
    {
        if (count($this->jsonParams) === 0)
            return (new Response(400))->setError("No JSON attributes supplied");

        $validator = V
            ::key("macAddress", V::stringType()->regex("/([a-fA-F0-9]{2}:){5}[a-fA-F0-9]{2}/"))
            ->key("name", V::anyOf(V::nullType(), V::stringType()->length(1, 128)), false);

        try { $validator->check($this->jsonParams); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        $this->jsonParams["macAddress"] = strtolower($this->jsonParams["macAddress"]);
        return new Response(200);
    }

    private function createNode() : Response
    {
        try
        {
            $sql = "INSERT INTO nodes " . sql_insert_string($this->jsonParams);
            database_query($this->pdo, $sql, array_values($this->jsonParams));
            
            return (new Response(200))->setBody(["nodeId" => $this->pdo->lastInsertId()]);
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
            else
            {
                error_log($ex);
                return new Response(500);
            }
        }
    }
}