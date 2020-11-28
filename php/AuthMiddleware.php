<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Slim\Exception\HttpInternalServerErrorException;

class AuthMiddleware
{
    private $redirects;

    public function __construct(bool $redirects)
    {
        $this->redirects = $redirects;
    }

    public function __invoke(Request $request, RequestHandler $handler) : Response
    {
        $cookie = FigRequestCookies::get($request, SESSION_COOKIE_NAME);

        if ($cookie->getValue() === null)
        {
            if ($this->redirects)
            {
                $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor("login");
                return (new Response())->withHeader("Location", $url)->withStatus(302);
            }
            else
            {
                return (new Response())->withHeader("Content-Type", "application/json")
                    ->getBody()->write(json_encode(["status" => 401]))->withStatus(401);
            }
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
                if ($this->redirects)
                {
                    $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor("login");
                    $response = (new Response())->withHeader("Location", $url)->withStatus(302);
                }
                else
                {
                    $response = (new Response())->withHeader("Content-Type", "application/json")
                        ->getBody()->write(json_encode(["status" => 401]))->withStatus(401);
                }

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
        catch (\PDOException $ex)
        {
            if (!$this->redirects)
            {
                return (new Response())->withHeader("Content-Type", "application/json")
                    ->getBody()->write(json_encode(["status" => 500]))->withStatus(500);
            }
            else throw new HttpInternalServerErrorException($request, null, $ex);
        }
    } 
}