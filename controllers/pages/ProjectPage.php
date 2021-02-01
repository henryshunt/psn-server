<?php
namespace App\Controllers\Pages;

use Psr\Http\Message\ServerRequestInterface as IRequest;
use Psr\Http\Message\ResponseInterface as IResponse;
use Slim\Psr7\Response as Response;
use Slim\Views\Twig;

class ProjectPage
{
    private $request;
    private $response;
    private $args;
    private $pdo;
    private $user;

    private $project = [];
    private $activeNodes = [];
    private $completedNodes = [];

    public function __invoke(IRequest $request, IResponse $response, array $args) : IResponse
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;
        $this->pdo = $this->request->getAttribute("pdo");
        $this->user = $this->request->getAttribute("user");

        if ($request->getMethod() === "GET")
            return $this->get();
        else return $response->withStatus(404);
    }


    private function get() : Response
    {
        $response = $this->readProject();
        if ($response->getStatusCode() !== 200)
            return $response;

        $response = $this->readProjectNodes();
        if ($response->getStatusCode() !== 200)
            return $response;

        $data =
        [
            "user" => $this->user,
            "project" => $this->project,
            "activeNodes" => $this->activeNodes,
            "completedNodes" => $this->completedNodes
        ];
        
        return Twig::fromRequest($this->request)
            ->render($this->response, "pages/project.twig", $data);
    }

    private function readProject() : Response
    {
        try
        {
            $sql = $this->readProjectSql();
            $query = database_query($this->pdo, $sql[0], $sql[1]);

            if (count($query) === 0)
                return $this->response->withStatus(404);

            if ($query[0]["userId"] !== $this->user["userId"])
                return $this->response->withStatus(403);

            $this->project["projectId"] = $this->args["projectId"];
            $this->project["name"] = $query[0]["name"];

            if ($query[0]["description"] === null)
                $this->project["description"] = "No Description Available";
            else $this->project["description"] = $query[0]["description"];

            $this->project["isActive"] = (bool)$query[0]["isActive"];
            return $this->response->withStatus(200);
        }
        catch (\PDOException $ex)
        {
            return $this->response->withStatus(500);
        }
    }

    private function readProjectSql() : array
    {
        $sql = "SELECT
                    p.userId,
                    p.name,
                    p.description,
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
                    
                WHERE p.projectId = ?
                LIMIT 1";

        $values = [$this->args["projectId"]];
        return [$sql, $values];
    }

    private function readProjectNodes() : Response
    {
        try
        {
            $sql = $this->readProjectNodesSql();
            $query = database_query($this->pdo, $sql[0], $sql[1]);

            foreach ($query as $node)
            {
                $newNode;
                $newNode["nodeId"] = $node["nodeId"];
                $newNode["location"] = $node["location"];

                if ($node["isActive"] === 1)
                {
                    if ($node["r_reportId"] !== null)
                    {
                        $reportTime = \DateTime::createFromFormat("Y-m-d H:i:s", $node["r_time"]);
                        $newNode["reportTime"] =
                            "Latest Report on " . $reportTime->format('d/m/Y \a\t H:i');

                        if ($node["r_airt"] !== null)
                            $newNode["latestReport"]["airt"] = $node["r_airt"] . "°C";
                        else $newNode["latestReport"]["airt"] = "None";

                        if ($node["r_relh"] !== null)
                            $newNode["latestReport"]["relh"] = $node["r_relh"] . "%";
                        else $newNode["latestReport"]["relh"] = "None";
                    }
                    else
                    {
                        $newNode["reportTime"] = "No Latest Report";
                        $newNode["latestReport"] = null;
                    }
                    
                    array_push($this->activeNodes, $newNode);
                }
                else array_push($this->completedNodes, $newNode);
            }

            return $this->response->withStatus(200);
        }
        catch (\PDOException $ex)
        {
            return $this->response->withStatus(500);
        }
    }

    private function readProjectNodesSql() : array
    {
        $sql = "SELECT
                    pn.nodeId,
                    pn.location,
                    pn.startedAt,
                    pn.endAt,
                    pn.interval,
                    r.reportId r_reportId,
                    r.time r_time,
                    r.airt r_airt,
                    r.relh r_relh,
                    r.batv r_batv,
                    (endAt IS NULL OR NOW() < endAt) isActive
                
                FROM projectNodes pn
                    LEFT JOIN reports r ON r.reportId = pn.latestReportId
                    
                WHERE pn.projectId = ?
                ORDER BY location";

        $values = [$this->args["projectId"]];
        return [$sql, $values];
    }
}