<?php
require_once "helpers.php";

if (array_key_exists(SESSION_COOKIE_NAME, $_COOKIE))
{
    $config = load_configuration(__DIR__ . "/../config.json");

    if ($config === false)
    {
        error_log("Error loading configuration");
        error_page(500);
    }
    
    try
    {
        $pdo = database_connect($config["databaseHost"], $config["databaseName"],
            $config["databaseUsername"], $config["databasePassword"]);
    }
    catch (Exception $ex)
    {
        error_log($ex);
        error_page(500);
    }


    // Check whether the token in the cookie is valid
    try
    {
        $sql = "SELECT * FROM users WHERE userId = 
                    (SELECT userId FROM tokens WHERE token = ? AND NOW() < expiresAt)
                LIMIT 1";

        $user = database_query($pdo, $sql, [$_COOKIE[SESSION_COOKIE_NAME]]);

        if (count($user) === 0)
        {
            // Remove the cookie to prevent unnecessary checks down the line
            setcookie(SESSION_COOKIE_NAME, "", time() - 1, SESSION_COOKIE_PATH);

            if (!defined("LOGIN_PAGE"))
            {
                header("Location: login.php");
                exit();
            }
        }
        else
        {
            $user = $user[0];
            
            if (defined("LOGIN_PAGE"))
            {
                header("Location: .");
                exit();
            }
        }
    }
    catch (PDOException $ex)
    {
        error_log($ex);
        error_page(500);
    }
}
else if (!defined("LOGIN_PAGE"))
{
    header("Location: login.php");
    exit();
}