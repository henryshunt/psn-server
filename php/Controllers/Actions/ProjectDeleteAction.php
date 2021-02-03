<?php
namespace Psn\Controllers\Actions;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpInternalServerErrorException;


class ProjectDeleteAction
{
    private $request;
    private $pdo;
    private $user;
    private $response;
    private $resArgs;

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->pdo = $request->getAttribute("pdo");
        $this->user = $request->getAttribute("user");
        $this->response = $response;
        $this->resArgs = $args;

        return $this->deleteProject();
    }

    private function deleteProject(): Response
    {
        try
        {
            $sql = "DELETE FROM projects WHERE projectId = ? AND userId = ?";
            $values = [$this->resArgs["projectId"], $this->user["userId"]];
            $affected = database_query_affected($this->pdo, $sql, $values);

            if ($affected === 0)
                throw new HttpNotFoundException($this->request);

            $this->response->getBody()->write(json_encode(["status" => 200]));
            return $this->response->withHeader("Content-Type", "application/json");
        }
        catch (\PDOException $ex)
        {
            throw new HttpInternalServerErrorException($this->request, null, $ex);
        }
    }
}