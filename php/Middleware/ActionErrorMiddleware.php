<?php
namespace Psn\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as Response;
use Slim\Exception\HttpException;
use Slim\Exception\HttpBadRequestException;


class ActionErrorMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler) : Response
    {
        try
        {
            return $handler->handle($request);
        }
        catch (HttpException $ex)
        {
            $status = $this->getExceptionStatusCode($ex);
            $response = (new Response($status))->withHeader("Content-Type", "application/json");

            $json = ["status" => $status];

            if ($ex->getMessage() !== "" && $status !== 500)
                $json["error"] = $ex->getMessage();

            // $this->logger->error($ex);

            $response->getBody()->write(json_encode($json));
            return $response;
        }
    }

    private function getExceptionStatusCode(HttpException $exception) : int
    {
        if ($exception instanceof HttpBadRequestException)
            return 400;
        else return 500;
    }
}
