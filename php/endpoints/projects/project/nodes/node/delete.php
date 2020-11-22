<?php
class EndpointProjectNodeDelete
{
    public function response() : Response
    {
        $validation = checkProjectAccess(
            $this->pdo, $this->resParams["projectId"], $this->user["userId"]);

        if ($validation->getStatus() !== 200)
            return $validation;

        return $this->deleteProjectNode();
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