<?php
namespace App\Controllers\Pages;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Exception\HttpInternalServerErrorException;


class ProjectsPage
{
    private $request;
    private $response;
    private $pdo;
    private $user;

    private $activeProjects = [];
    private $inactiveProjects = [];
    private $json;

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->response = $response;
        $this->pdo = $this->request->getAttribute("pdo");
        $this->user = $this->request->getAttribute("user");

        $this->readProjects();

        $viewData =
        [
            "user" => $this->user,
            "activeProjects" => $this->activeProjects,
            "inactiveProjects" => $this->inactiveProjects
        ];
        
        return Twig::fromRequest($this->request)
            ->render($this->response, "pages/projects.twig", $viewData);
    }

    private function readProjects(): void
    {
        try
        {
            $sql = $this->readProjectsSql();
            $query = database_query($this->pdo, $sql[0], $sql[1]);

            foreach ($query as $project)
            {
                $newProject = [];
                $newProject["projectId"] = $project["projectId"];
                $newProject["name"] = $project["name"];

                if ($project["nodeCount"] === null)
                    $newProject["nodeCount"] = "0 Sensor Nodes";
                else if ($project["nodeCount"] === 1)
                    $newProject["nodeCount"] = "1 Sensor Node";
                else $newProject["nodeCount"] = $project["nodeCount"] . " Sensor Nodes";

                if ($project["nodeCount"] !== null)
                {
                    $startedAt = \DateTime::createFromFormat("Y-m-d H:i:s", $project["startedAt"]);
                    $newProject["dateRange"] = "From " . $startedAt->format("d/m/Y");

                    if ($project["endAt"] !== null)
                    {
                        $endAt = \DateTime::createFromFormat("Y-m-d H:i:s", $project["endAt"]);
                        $newProject["dateRange"] .= " to " . $endAt->format("d/m/Y");
                    }
                    else $newProject["dateRange"] .= ", indefinitely";
                }
                else $newProject["dateRange"] = "";

                if ($project["isActive"] === 1)
                    array_push($this->activeProjects, $newProject);
                else array_push($this->inactiveProjects, $newProject);
            }
        }
        catch (\PDOException $ex)
        {
            throw new HttpInternalServerErrorException($this->request, null, $ex);
        }
    }

    private function readProjectsSql(): array
    {
        $sql = "SELECT
                    p.projectId,
                    p.name,
                    pn.startedAt,
                    pn.endAt,
                    pn.nodeCount,
                    (pn.nodeCount IS NOT NULL AND (pn.endAt IS NULL OR NOW() < pn.endAt)) isActive
                
                FROM projects p
                    LEFT JOIN (
                        SELECT
                            projectId,
                            MIN(startedAt) startedAt,
                            MAX(endAt) endAt,
                            COUNT(*) nodeCount

                        FROM projectNodes
                            GROUP BY projectId
                    ) pn ON pn.projectId = p.projectId
                
                WHERE userId = ?
                ORDER BY p.name";

        $values = [$this->user["userId"]];
        return [$sql, $values];
    }
}