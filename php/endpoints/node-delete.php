<?php
use Respect\Validation\Validator as V;
use Respect\Validation\Exceptions\ValidationException;

class EndpointNodeGet
{
    private $pdo;
    private $user;
    private $restParams;

    public function __construct(PDO $pdo, array $user)
    {
        $this->pdo = $pdo;
        $this->user = $user;
    }

    public function response(array $restParams) : Response
    {
        $this->restParams = $restParams;

        if (!$this->user["privNodes"])
            return (new Response(403))->setBody("Only privileged users can delete nodes");

        return $this->deleteNode();
    }

    private function deleteNode() : Response
    {
        try
        {
            $sql = "DELETE FROM nodes WHERE nodeId = ?";
            $affected = database_query_affected($pdo, $sql, [$this->restParams["nodeId"]]);
            return new Response($affected > 0 ? 200 : 404);
        }
        catch (PDOException $ex)
        {
            error_log($ex);
            return new Response(500);
        }
    }
}