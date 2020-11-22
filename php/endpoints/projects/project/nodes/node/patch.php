<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointProjectNodePatch
{
    private $jsonParams;

    public function response() : Response
    {
        $validation = checkProjectAccess(
            $this->pdo, $this->resParams["projectId"], $this->user["userId"]);

        if ($validation->getStatus() !== 200)
            return $validation;

        $validation = $this->checkProjectNodeExists();
        if ($validation->getStatus() !== 200)
            return $validation;

        $validation = $this->validateUrlParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        // If stop=true then stop the projectNode instead of updating attributes
        if (keyExistsMatches("stop", "true", $this->urlParams))
            return $this->stopProjectNode();

        return new Response(400);
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

    private function checkProjectNodeExists() : Response
    {
        try
        {
            $projectNode = api_get_project_node($this->pdo,
                $this->resParams["projectId"], $this->resParams["nodeId"]);

            if ($project === null)
                return new Response(404);
            else return new response(200);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }

    private function stopProjectNode() : Response
    {
        try
        {
            $sql = "UPDATE projectNodes SET endAt = NOW()
                        WHERE projectId = ? AND nodeId = ? AND (endAt IS NULL OR NOW() < endAt)";

            $query = database_query($this->pdo, $sql,
                [$this->resParams["projectId"], $this->resParams["nodeId"]]);
                
            return new Response(200);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }
}