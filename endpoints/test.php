<?php
function api_test_get()
{
    global $pdo;
    $user_id = api_authenticate($pdo);
    echo $user_id;
    
    return [200, null];
}

function api_tokens_post()
{
    if (isset($_SERVER["PHP_AUTH_USER"]) === true)
    {
        try
        {
            global $pdo;
            $sql = "SELECT user_id FROM users WHERE username = ? AND password = ?";
            $query = database_query($pdo, $sql, [$_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"]]);

            if (count($query) > 0)
            {
                try
                {
                    $token = get_random_string(64);

                    $sql = "INSERT INTO tokens (user_id, token) VALUES (?, ?)";
                    $query = database_query($pdo, $sql, [$query[0]["user_id"], $token]);
                    return [200, json_encode(["token" => $token])];
                }
                catch (PDOException $ex)
                {
                    return [500, $ex->getMessage()];
                }
            }
            else return [401, null];
        }
        catch (PDOException $ex)
        {
            return [500, null];
        }
    }
    else
    {
        header("WWW-Authenticate: Basic");
        api_respond(401);
    }
}