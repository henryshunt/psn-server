<?php
class EndpointNodeGet extends Endpoint
{
    public function response(array $resParams) : Response
    {
        $this->resParams = $resParams;

        if (!$this->user["privNodes"])
            return (new Response(403))->setBody("Only privileged users can delete nodes");

        return $this->deleteNode();
    }

    private function deleteNode() : Response
    {
        try
        {
            $sql = "DELETE FROM nodes WHERE nodeId = ?";
            $affected = database_query_affected($pdo, $sql, [$this->resParams["nodeId"]]);
            return new Response($affected > 0 ? 200 : 404);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }
}