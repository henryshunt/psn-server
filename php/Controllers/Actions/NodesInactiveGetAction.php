<?php
namespace Psn\Controllers\Actions;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpInternalServerErrorException;

class NodesInactiveGetAction
{
    private $request;
    private $response;
    private $pdo;
    private $user;

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->response = $response;
        $this->pdo = $request->getAttribute("pdo");
        $this->user = $request->getAttribute("user");

        if (!$this->user["privNodes"])
            throw new HttpForbiddenException($request);

        $this->readNodes();
        return $response;
    }

    private function readNodes(): void
    {
        try
        {
            $query = database_query($this->pdo, $this->readNodesSql());
            $this->response->getBody()->write(json_encode($query));
        }
        catch (\PDOException $ex)
        {
            throw new HttpInternalServerErrorException($this->request, null, $ex);
        }
    }

    private function readNodesSql(): string
    {
        $sql = "SELECT
                    nodeId,
                    macAddress,
                    name
                FROM nodes
                WHERE nodeId NOT IN
                    (SELECT nodeId FROM projectNodes WHERE endAt IS NULL OR NOW() < endAt)
                ORDER BY macAddress";

        return $sql;
    }
}