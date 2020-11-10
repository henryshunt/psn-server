<?php
include_once "../php/helpers.php";

$config = load_configuration("../config.json");
if ($config === false)
    header("Location: http://localhost/psn-server/login.php?failure=1");

try
{
    $pdo = database_connect($config["databaseHost"], $config["databaseName"],
        $config["databaseUsername"], $config["databasePassword"]);
}
catch (Exception $ex)
{
    header("Location: http://localhost/psn-server/login.php?failure=1");
}


try
{
    $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
    $query = database_query($pdo, $sql, [$_POST["username"], $_POST["password"]]);

    if (count($query) > 0)
    {
        try
        {
            $token = get_random_string(64);

            $sql = "INSERT INTO tokens (userId, token) VALUES (?, ?)";
            $query = database_query($pdo, $sql, [$query[0]["userId"], $token]);

            setcookie("session", $token, time() + (3600 * 1), "/");
            header("Location: http://localhost/psn-server");
            exit();
        }
        catch (PDOException $ex)
        {
            header("Location: http://localhost/psn-server/login.php?failure=1");
            exit();
        }
    }
    else header("Location: http://localhost/psn-server/login.php?failure=1");
}
catch (PDOException $ex)
{
    header("Location: http://localhost/psn-server/login.php?failure=1");
}