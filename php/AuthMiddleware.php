<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;

class AuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler) : Response
    {
        $cookie = FigRequestCookies::get($request, SESSION_COOKIE_NAME);

        if ($cookie === null)
        {
            $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor("login");
            return (new Response())->withHeader("Location", $url)->withStatus(302);
        }

        // Check whether the token in the cookie is valid
        try
        {
            $sql = "SELECT * FROM users WHERE userId = 
                        (SELECT userId FROM tokens WHERE token = ? AND NOW() < expiresAt)
                    LIMIT 1";
    
            $user = database_query($request->getAttribute("pdo"), $sql, [$cookie->getValue()]);
    
            if (count($user) === 0)
            {
                $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor("login");
                $response = (new Response())->withHeader("Location", $url)->withStatus(302);

                // Remove the cookie to prevent unnecessary checks down the line
                $response = FigResponseCookies::expire($response, SESSION_COOKIE_NAME);
                
                return $response;
            }
            else
            {
                $user[0]["privNodes"] = (bool)$user[0]["privNodes"];
                $user[0]["privUsers"] = (bool)$user[0]["privUsers"];
                
                return $handler->handle($request->withAttribute("user", $user[0]));
            }
        }
        catch (PDOException $ex)
        {
            return (new Response())->withStatus(500);
        }
    }
}