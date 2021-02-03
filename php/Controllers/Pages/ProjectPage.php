<?php
namespace Psn\Controllers\Pages;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;


class ProjectPage
{
    private $request;
    private $pdo;
    private $user;
    private $resArgs;

    private $project = [];
    private $activeNodes = [];
    private $inactiveNodes = [];

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->pdo = $request->getAttribute("pdo");
        $this->user = $request->getAttribute("user");
        $this->resArgs = $args;

        $this->readProject();
        $this->readProjectNodes();

        $viewArgs =
        [
            "user" => $this->user,
            "project" => $this->project,
            "activeNodes" => $this->activeNodes,
            "inactiveNodes" => $this->inactiveNodes
        ];
        
        return Twig::fromRequest($request)
            ->render($response, "pages/project.twig", $viewArgs);
    }

    private function readProject(): void
    {
        try
        {
            $sql = $this->readProjectSql();
            $query = database_query($this->pdo, $sql[0], $sql[1]);

            if (count($query) === 0)
                throw new HttpNotFoundException($this->request, null, $ex);

            $this->project["projectId"] = $this->resArgs["projectId"];
            $this->project["name"] = $query[0]["name"];

            if ($query[0]["description"] === null)
                $this->project["description"] = "No Description Available";
            else $this->project["description"] = $query[0]["description"];

            $this->project["isActive"] = (bool)$query[0]["isActive"];
        }
        catch (\PDOException $ex)
        {
            throw new HttpInternalServerErrorException($this->request, null, $ex);
        }
    }

    private function readProjectSql(): array
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
                    AND userId = ?
                LIMIT 1";

        $values =
        [
            $this->resArgs["projectId"],
            $this->user["userId"]
        ];

        return [$sql, $values];
    }

    private function readProjectNodes(): void
    {
        try
        {
            $sql = $this->readProjectNodesSql();
            $query = database_query($this->pdo, $sql[0], $sql[1]);

            foreach ($query as $node)
            {
                $newNode = [];
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
                else array_push($this->inactiveNodes, $newNode);
            }
        }
        catch (\PDOException $ex)
        {
            throw new HttpInternalServerErrorException($this->request, null, $ex);
        }
    }

    private function readProjectNodesSql(): array
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

        return [$sql, [$this->resArgs["projectId"]]];
    }
}