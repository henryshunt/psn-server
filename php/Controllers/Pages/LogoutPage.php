<?php
namespace Psn\Controllers\Pages;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Slim\Exception\HttpInternalServerErrorException;


class LogoutPage
{
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $cookie = FigRequestCookies::get($request, SESSION_COOKIE_NAME);

        try
        {
            $sql = "DELETE FROM tokens WHERE token = ?";
            database_query($request->getAttribute("pdo"), $sql, [$cookie->getValue()]);
            
            $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor("login");
            $response = (new Response(302))->withHeader("Location", $url);
            $response = FigResponseCookies::expire($response, SESSION_COOKIE_NAME);
            return $response;
        }
        catch (Exception $ex)
        {
            throw new HttpInternalServerErrorException($request, null, $ex);
        }
    }
}