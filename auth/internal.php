<?php
include_once "../php/helpers.php";

if (array_key_exists(SESSION_COOKIE_NAME, $_COOKIE))
{
    header("Location: ..");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" ||
    !array_key_exists("username", $_POST) || !array_key_exists("password", $_POST))
{
    header("Location: ../login.php?error=request");
    exit();
}


$config = load_configuration("../config.json");

if ($config === false)
{
    error_log("Error loading configuration");
    header("Location: ../login.php?error=internal");
    exit();
}

try
{
    $pdo = database_connect($config["databaseHost"], $config["databaseName"],
        $config["databaseUsername"], $config["databasePassword"]);
}
catch (Exception $ex)
{
    error_log($ex);
    header("Location: ../login.php?error=internal");
    exit();
}


try
{
    $sql = "SELECT userId, password FROM users WHERE username = ? LIMIT 1";
    $query = database_query($pdo, $sql, [$_POST["username"]]);

    if (count($query) > 0)
    {
        if ($_POST["password"] === $query[0]["password"])
        {
            $token = random_string(SESSION_TOKEN_LENGTH);
            $expiresAt = time() + SESSION_EXPIRE_AFTER;
            $expiresAtString = date("Y-m-d H:i:s", $expiresAt);

            $sql = "INSERT INTO tokens (userId, token, expiresAt) VALUES (?, ?, ?)";
            $query = database_query($pdo, $sql,
                [$query[0]["userId"], $token, $expiresAtString]);

            setcookie(SESSION_COOKIE_NAME, $token, $expiresAt, SESSION_COOKIE_PATH);
            header("Location: ..");
        }
        else header("Location: ../login.php?error=password");
    }
    else header("Location: ../login.php?error=username");
}
catch (PDOException $ex)
{
    error_log($ex);
    header("Location: ../login.php?error=internal");
}