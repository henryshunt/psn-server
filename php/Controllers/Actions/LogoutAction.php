<?php
namespace Psn\Controllers\Actions;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;


class LogoutAction
{
    private $request;
    private $response;

    public function __invoke(Request $request, Response $response, array $args) : Response
    {
        $this->request = $request;
        $this->response = $response;

        $cookie = FigRequestCookies::get($request, SESSION_COOKIE_NAME);

        if ($cookie === null)
            return $this->redirectToLogin();

        try
        {
            $sql = "DELETE FROM tokens WHERE token = ?";
            database_query($this->request->getAttribute("pdo"), $sql, [$cookie->getValue()]);

            $this->response = FigResponseCookies::expire($response, SESSION_COOKIE_NAME);
            return $this->redirectToLogin();
        }
        catch (Exception $ex)
        {
            return (new Response())->withStatus(500);
        }
    }

    private function redirectToLogin() : Response
    {
        $url = RouteContext::fromRequest($this->request)->getRouteParser()->urlFor("login");
        return $this->response->withHeader("Location", $url)->withStatus(302);
    }
}