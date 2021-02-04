<?php
namespace Psn\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Slim\Exception\HttpInternalServerErrorException;


class AuthMiddleware
{
    private $mode;
    private $request;
    private $pdo;

    public function __construct(string $mode)
    {
        if ($mode !== "AUTH_CONT_NAUTH_LOGIN" &&
            $mode !== "AUTH_CONT_NAUTH_RETURN" &&
            $mode !== "AUTH_INDEX_NAUTH_CONT")
        {
            throw new ValueError("Invalid mode");
        }
        else $this->mode = $mode;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $this->request = $request;
        $this->pdo = $request->getAttribute("pdo");
        
        try
        {
            $auth = $this->checkAuth();

            if ($this->mode === "AUTH_CONT_NAUTH_LOGIN")
            {
                if (!$auth[0])
                {
                    $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor("login");
                    return (new Response(302))->withHeader("Location", $url);
                }
            }
            else if ($this->mode === "AUTH_CONT_NAUTH_RETURN")
            {
                if (!$auth[0])
                    return new Response(401);
            }
            else if ($this->mode === "AUTH_INDEX_NAUTH_CONT")
            {
                if ($auth[0])
                {
                    $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor("projects");
                    return (new Response(302))->withHeader("Location", $url);
                }
            }

            if ($auth[2] !== null)
                $response = $handler->handle($request->withAttribute("user", $auth[2]));
            else $response = $handler->handle($request);

            // Remove the now-useless cookie to prevent unnecessary checks down the line
            if ($authed[1])
                $response = FigResponseCookies::expire($response, SESSION_COOKIE_NAME);
                
            return $response;
        }
        catch (\PDOException $ex)
        {
            if ($this->mode === "AUTH_CONT_NAUTH_RETURN")
                return new Response(500);
            else throw new HttpInternalServerErrorException($request, null, $ex);
        }
    }

    /// Return value: 0) authenticated? 1) should remove cookie? 2) user info.
    private function checkAuth(): array
    {
        $cookie = FigRequestCookies::get($this->request, SESSION_COOKIE_NAME);
        
        if ($cookie->getValue() === null)
            return [false, false, null];

        // Check whether the token in the cookie is valid
        $sql = "SELECT * FROM users WHERE userId = 
                    (SELECT userId FROM tokens WHERE token = ? AND NOW() < expiresAt)
                LIMIT 1";

        $user = database_query($this->pdo, $sql, [$cookie->getValue()]);

        if (count($user) === 1)
        {
            $user[0]["privNodes"] = (bool)$user[0]["privNodes"];
            $user[0]["privUsers"] = (bool)$user[0]["privUsers"];
            return [true, false, $user[0]];
        }
        else return [false, true, null];
    }
}