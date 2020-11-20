<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointProjectsPost extends Endpoint
{
    private $jsonParams;

    public function response() : Response
    {
        $loadJson = $this->loadJsonParams();
        if ($loadJson->getStatus() !== 200)
            return $loadJson;

        $validation = $this->validateParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        return $this->createProject();
    }

    public function loadJsonParams() : Response
    {
        $json = json_decode(file_get_contents("php://input"));

        if (gettype($json) !== "object")
            return (new Response(400))->setError("Invalid JSON object supplied");

        $json = (array)$json;
        $json = filter_keys($json, ["name", "description"]);

        $this->jsonParams = $json;
        return new Response(200);
    }

    private function validateParams() : Response
    {
        if (count($this->jsonParams) === 0)
            return (new Response(400))->setError("No JSON attributes supplied");

        $validator = V
            ::key("name", V::stringType()->length(1, 128))
            ->key("description", V::anyOf(
                V::nullType(), V::stringType()->length(1, 255)), false);

        try { $validator->check($this->jsonParams); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        return new Response(200);
    }

    private function createProject() : Response
    {
        try
        {
            $values = $this->jsonParams;
            $values["userId"] = $this->user["userId"];

            $sql = "INSERT INTO projects " . sql_insert_string($values);
            database_query($this->pdo, $sql, array_values($values));

            return (new Response(200))->setBody(["projectId" => $this->pdo->lastInsertId()]);
        }
        catch (PDOException $ex)
        {
            if ($ex->errorInfo[1] === 1452 &&
                strpos($ex->errorInfo[2], "FOREIGN KEY (`userId`)") !== false)
            {
                return new Response(401);
            }
            else if ($ex->errorInfo[1] === 1062 &&
                strpos($ex->errorInfo[2], "for key 'name'") !== false)
            {
                return (new Response(400))->setError("name is not unique within user");
            }
            else
            {
                error_log($ex);
                return new Response(500);
            }
        }
    }
}