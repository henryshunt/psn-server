<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointProjectPatch extends Endpoint
{
    private $jsonParams;

    public function response() : Response
    {
        $validation = checkProjectAccess(
            $this->pdo, $this->resParams["projectId"], $this->user["userId"]);

        if ($validation->getStatus() !== 200)
            return $validation;
        
        $validation = $this->validateUrlParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        // If stop=true then stop the project instead of updating attributes
        if (keyExistsMatches("stop", "true", $this->urlParams))
            return $this->stopProject();

        $validation = $this->validateJsonParams();
        if ($validation->getStatus() !== 200)
            return $validation;

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

    private function validateJsonParams() : Response
    {
        $json = json_decode(file_get_contents("php://input"));

        if (gettype($json) !== "object")
            return (new Response(400))->setError("Invalid JSON object supplied");

        $json = filter_keys((array)$json, ["name", "description"]);

        if (count($json) === 0)
            return (new Response(400))->setError("No JSON attributes supplied");

        $validator = V
            ::key("name", V::stringType()->length(1, 128))
            ->key("description", V::anyOf(
                V::nullType(), V::stringType()->length(1, 255)), false);

        try { $validator->check($json); }
        catch (ValidationException $ex)
        {
            return (new Response(400))->setError($ex->getMessage());
        }

        $this->jsonParams = $json;
        return new Response(200);
    }

    private function updateProject() : Response
    {
        try
        {
            $sql = "UPDATE projects SET %s WHERE projectId = ?";
            $sql = sprintf($sql, sql_update_string(array_keys($this->jsonParams)));

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
                return (new Response(400))->setError("name is not unique within user");
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
            $sql = "UPDATE projectNodes SET endAt = NOW()
                        WHERE projectId = ? AND (endAt IS NULL OR NOW() < endAt)";

            $query = database_query($this->pdo, $sql, [$this->resParams["projectId"]]);
            return new Response(200);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }
}