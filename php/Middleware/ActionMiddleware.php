<?php
namespace Psn\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as Response;
use Slim\Exception\HttpSpecializedException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorisedException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpInternalServerErrorException;

class ActionMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        try
        {
            $response = $handler->handle($request);
            $response = $response->withHeader("Content-Type", "application/json");

            if ($response->getBody()->getSize() === 0)
            {
                $response->getBody()->write(
                    json_encode(["status" => $response->getStatusCode()]));
            }

            return $response;
        }
        catch (HttpSpecializedException $ex)
        {
            $status = $this->getExceptionStatus($ex);
            $response = (new Response($status))->withHeader("Content-Type", "application/json");

            $json = ["status" => $status];
            if ($status !== 500 && $ex->getMessage() !== "")
                $json["error"] = $ex->getMessage();

            $response->getBody()->write(json_encode($json));
            return $response;
        }
        // catch (Exception $ex)
        // {
        //     $response = (new Response(500))->withHeader("Content-Type", "application/json");
        //     $response->getBody()->write(json_encode(["status" => $status]));
        //     return $response;
        // }
    }

    private function getExceptionStatus(HttpSpecializedException $exception): int
    {
        if ($exception instanceof HttpBadRequestException)
            return 400;
        else if ($exception instanceof HttpUnauthorisedException)
            return 401;
        else if ($exception instanceof HttpForbiddenException)
            return 403;
        else if ($exception instanceof HttpNotFoundException)
            return 404;
        else if ($exception instanceof HttpInternalServerErrorException)
            return 500;
        else return 500;
    }
}
