<?php
class EndpointProjectDelete extends Endpoint
{
    public function response() : Response
    {
        $validation = checkProjectAccess($this->pdo,
            $this->resParams["projectId"], $this->user["userId"]);

        if ($validation->getStatus() !== 200)
            return $validation;

        return $this->deleteProject();
    }

    private function deleteProject() : Response
    {
        try
        {
            $sql = "DELETE FROM projects WHERE projectId = ?";
            $affected = database_query_affected($this->pdo, $sql, [$this->resParams["projectId"]]);
            return new Response($affected > 0 ? 200 : 404);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }
}