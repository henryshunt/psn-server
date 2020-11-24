<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointNodePatch extends Endpoint
{
    private $jsonParams;

    public function response() : Response
    {
        if (!$this->user["privNodes"])
            return (new Response(403))->setBody("Only privileged users can update nodes");

        $validation = $this->checkNodeExists();
        if ($validation->getStatus() !== 200)
            return $validation;

        $validation = $this->validateJsonParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        return $this->updateNode();
    }

    private function checkNodeExists() : Response
    {
        try
        {
            if (api_get_node($this->pdo, $this->resParams["nodeId"]) === null)
                return new Response(404);
            else return new Response(200);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }

    private function validateJsonParams() : Response
    {
        $json = json_decode(file_get_contents("php://input"));

        if (gettype($json) !== "object")
            return (new Response(400))->setError("Invalid JSON object supplied");

        $json = filter_keys((array)$json, ["name"]);

        if (count($json) === 0)
            return (new Response(400))->setError("No JSON attributes supplied");

        $validator = V
            ::key("name", V::anyOf(V::nullType(), V::stringType()->length(1, 128)), false);

        try { $validator->check($json); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        $this->jsonParams = $json;
        return new Response(200);
    }

    private function updateNode() : Response
    {
        try
        {
            $sql = "UPDATE nodes SET %s WHERE nodeId = ?";
            $sql = sprintf($sql, sql_update_string(array_keys($this->jsonParams)));

            $values = array_values($this->jsonParams);
            array_push($values, $this->resParams["nodeId"]);

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