<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointNodePatch
{
    private $pdo;
    private $user;
    private $restParams;
    private $jsonParams;

    public function __construct(PDO $pdo, array $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function response(array $restParams) : Response
    {
        $this->restParams = $restParams;

        if (!$this->user["privNodes"])
            return (new Response(403))->setBody("Only privileged users can update nodes");

        $loadJson = $this->loadJsonParams();
        if ($loadJson->getStatus() !== 200)
            return $loadJson;

        $validation = $this->validateParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        return $this->updateNode();
    }

    public function loadJsonParams() : Response
    {
        $json = json_decode(file_get_contents("php://input"));

        if (gettype($json) !== "object")
            return (new Response(400))->setError("Invalid JSON object supplied");

        $json = (array)$json;
        $json = filter_keys($json, ["name"]);

        $this->jsonParams = $json;
        return new Response(200);
    }

    private function validateParams() : Response
    {
        if (count($this->jsonParams) === 0)
            return (new Response(400))->setError("No JSON attributes supplied");

        $validator = V
            ::key("name", V::anyOf(V::nullType(), V::stringType()->length(1, 128)), false);

        try { $validator->check($this->jsonParams); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        try
        {
            if (api_get_node($this->pdo, $this->restParams["nodeId"]) === null)
                return new Response(404);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }

        return new Response(200);
    }

    private function updateNode() : Response
    {
        try
        {
            $sql = "UPDATE nodes SET %s WHERE nodeId = ?";
            $sql = sprintf($sql, sql_update_string($this->jsonParams));

            $values = array_values($this->jsonParams);
            array_push($values, $this->restParams["nodeId"]);

            database_query($this->pdo, $sql, $values);
            return new Response(200);
        }
        catch (PDOException $ex)
        {
            if ($ex->errorInfo[1] === 1062 &&
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