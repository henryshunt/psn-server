<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointProjectNodesPost extends Endpoint
{
    private $jsonParams;

    public function response() : Response
    {
        $validation = checkProjectAccess($this->pdo,
            $this->resParams["projectId"], $this->user["userId"]);

        if ($validation->getStatus() !== 200)
            return $validation;


        $validation = $this->validateJsonParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        $validation = $this->checkCanCreateProjectNode();
        if ($validation->getStatus() !== 200)
            return $validation;

        return $this->createProjectNode();
    }

    private function validateJsonParams() : Response
    {
        $loadJson = $this->loadJsonParams();
        if ($loadJson->getStatus() !== 200)
            return $loadJson;

        if (count($this->jsonParams) === 0)
            return (new Response(400))->setError("No JSON attributes supplied");

        $validator = V
            ::key("nodeId", V::intType()->digit()->min(0)->max(MYSQL_MAX_INT))
            ->key("location", V::stringType()->length(1, 128))
            ->key("endAt", V::anyOf(
                V::nullType(), V::stringType()->dateTime("Y-m-d H:i:s")))
            ->key("interval", V::in([1, 2, 5, 10, 15, 20, 30], true))
            ->key("batchSize", V::intType()->digit()->min(0)->max(MYSQL_MAX_TINYINT));

        try { $validator->check($this->jsonParams); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        if ($this->jsonParams["endAt"] !== null)
        {
            $endAt = DateTime::createFromFormat("Y-m-d H:i:s", $this->jsonParams["endAt"]);

            if ($endAt <= new DateTime())
                return (new response(400))->setBody("endAt must be in the future");
        }

        return new Response(200);
    }

    private function loadJsonParams() : Response
    {
        $json = json_decode(file_get_contents("php://input"));

        if (gettype($json) !== "object")
            return (new Response(400))->setError("Invalid JSON object supplied");

        $json = (array)$json;

        $json = filter_keys($json,
            ["nodeId", "location", "endAt", "interval", "batchSize"]);

        $this->jsonParams = $json;
        return new Response(200);
    }

    private function checkCanCreateProjectNode() : Response
    {
        try
        {
            $this->pdo->exec("LOCK TABLE projectNodes WRITE");

            if (api_get_project_node($this->pdo, $this->resParams["projectId"],
                $this->jsonParams["nodeId"]) !== null)
            {
                return (new Response(400))->setError("nodeId already exists in project");
            }

            // Make sure the node is not already active
            $sql = "SELECT 1 FROM projectNodes
                    WHERE nodeId = ? AND (endAt IS NULL OR NOW() < endAt)";

            $query = database_query($this->pdo, $sql, [$this->jsonParams["nodeId"]]);

            if (count($query) > 0)
                return (new Response(400))->setError("nodeId is already active");

            return new Response(200);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }

    private function createProjectNode() : Response
    {
        try
        {
            $values = $this->jsonParams;
            $values["projectId"] = $this->resParams["projectId"];
            $values["nodeId"] = $this->jsonParams["nodeId"];
            
            $sql = "INSERT INTO projectNodes " . sql_insert_string($values);
            database_query($this->pdo, $sql, array_values($values));

            return new Response(200);
        }
        catch (PDOException $ex)
        {
            if ($ex->errorInfo[1] === 1452 &&
                strpos($ex->errorInfo[2], "FOREIGN KEY (`projectId`)") !== false)
            {
                return new Response(404);
            }
            else if ($ex->errorInfo[1] === 1452 &&
                strpos($ex->errorInfo[2], "FOREIGN KEY (`nodeId`)") !== false)
            {
                return (new Response(400))->setError("nodeId does not exist");
            }
            else if ($ex->errorInfo[1] === 1062 &&
                strpos($ex->errorInfo[2], "for key 'projectId_location'") !== false)
            {
                return (new Response(400))->setError("location is not unique within project");
            }
            else
            {
                error_log($ex);
                return new Response(500);
            }
        }
    }
}