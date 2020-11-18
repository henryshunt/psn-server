<?php
class EndpointProjectNodeDelete
{
    private $pdo;
    private $user;
    private $resParams;

    public function __construct(PDO $pdo, array $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function response(array $resParams) : Response
    {
        $this->resParams = $resParams;

        $validation = $this->validateParams();
        if ($validation->getStatus() !== 200)
            return $validation;

        return $this->deleteProjectNode();
    }

    private function validateParams() : Response
    {
        // Check the project exists and the user owns it
        try
        {
            $project = api_get_project($this->pdo, $this->resParams["projectId"]);

            if ($project === null)
                return new Response(404);
            else if ($project["userId"] !== $this->user["userId"])
                return new Response(403);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }

    private function deleteProjectNode() : Response
    {
        try
        {
            $sql = "DELETE FROM projectNodes WHERE projectId = ? AND nodeId = ?";
            $affected = database_query_affected($this->pdo, $sql,
                [$this->resParams["projectId"], $this->resParams["nodeId"]]);

            return new Response($affected > 0 ? 200 : 404);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }
}