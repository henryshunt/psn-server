<?php
namespace Psn\Controllers\Actions;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;


class ProjectDeleteAction
{
    private $request;
    private $pdo;
    private $user;
    private $resArgs;

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->pdo = $request->getAttribute("pdo");
        $this->user = $request->getAttribute("user");
        $this->resArgs = $args;

        $this->deleteProject();
        return $response;
    }

    private function deleteProject(): void
    {
        try
        {
            $sql = "DELETE FROM projects WHERE projectId = ? AND userId = ?";
            $values = [$this->resArgs["projectId"], $this->user["userId"]];
            $affected = database_query_affected($this->pdo, $sql, $values);

            if ($affected === 0)
                throw new HttpNotFoundException($this->request);
        }
        catch (\PDOException $ex)
        {
            throw new HttpInternalServerErrorException($this->request, $ex->getMessage(), $ex);
        }
    }
}