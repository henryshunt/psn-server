<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointProjectPatch
{
    private $pdo;
    private $user;
    private $resParams;
    private $urlParams;
    private $jsonParams;

    public function __construct(PDO $pdo, array $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function response(array $resParams) : Response
    {
        $this->resParams = $resParams;
        $this->urlParams = $_GET;

        $validation = $this->validateUrlParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        // If stop=true then stop the project instead of updating attributes
        if (array_key_exists("stop", $this->urlParams) &&
            $this->urlParams["stop"] === "true")
        {
            $exists = $this->checkProjectExists();
            if ($exists->getStatus() !== 200)
                return $exists;

            return $this->stopProject();
        }

        $loadJson = $this->loadJsonParams();
        if ($loadJson->getStatus() !== 200)
            return $loadJson;

        $validation = $this->validateJsonParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        $exists = $this->checkProjectExists();
        if ($exists->getStatus() !== 200)
            return $exists;

        return $this->updateProject();
    }

    private function validateUrlParams() : Response
    {
        $validator = V::key("stop", V::in(["true", "false"], true), false);

        try { $validator->check($this->urlParams); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        return new Response(200);
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

    private function validateJsonParams() : Response
    {
        if (count($this->jsonParams) === 0)
            return (new Response(400))->setError("No JSON attributes supplied");

        $validator = V
            ::key("name", V::stringType()->length(1, 128))
            ->key("description", V::anyOf(V::nullType(), V::stringType()->length(1, 255)), false);

        try { $validator->check($this->jsonParams); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        return new Response(200);
    }

    private function checkProjectExists() : Response
    {
        // Check the project exists and the user owns it
        try
        {
            $project = api_get_project($this->pdo, $this->resParams["projectId"]);

            if ($project === null)
                return new Response(404);
            else if ($project["userId"] !== $this->user["userId"])
                return new Response(403);
            else return new response(200);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }

    private function updateProject() : Response
    {
        try
        {
            $sql = "UPDATE projects SET %s WHERE projectId = ?";
            $sql = sprintf($sql, sql_update_string($this->jsonParams));

            $values = array_values($this->jsonParams);
            array_push($values, $this->resParams["projectId"]);

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

    private function stopProject() : Response
    {
        try
        {
            $sql = "UPDATE projectNodes SET (endAt = NOW())
                        WHERE projectId = ? AND (endAt IS NULL OR NOW() < endAt)";

            $query = database_query($pdo, $sql, [$this->resParams["projectId"]]);
            return new Response(200);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }
}