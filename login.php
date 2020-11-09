<?php
require_once("resources/routines/helpers.php");
require_once("resources/routines/config.php");

$config = new Config();
if (!$config->load_config("config.ini"))
    die("Configuration error");
$db_connection = database_connection($config);
if (!$db_connection) die("Database error");

$session = try_loading_session($db_connection);
if ($session === FALSE) die("Session error");
if ($session !== NULL)
{
    header("Location: .");
    exit();
}
?>

<meta charset="UTF-8">
<!DOCTYPE html>

<html>
    <head>
        <title>Phenotyping Sensor Network</title>
        <meta name="viewport" content="width=450px">

        <link href="https://fonts.googleapis.com/css?family=Istok+Web:400,400i,700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons&display=block" rel="stylesheet">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js" type="text/javascript"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" type="text/javascript"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.27/moment-timezone-with-data-10-year-range.min.js" type="text/javascript"></script>
        <link href="resources/styles/globals.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/config.js.php" type="text/javascript"></script>
        <script src="resources/scripts/helpers.js" type="text/javascript"></script>

        <link href="resources/styles/grouping.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/modal.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/external/bootstrap.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/pages/login.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/pages/login.js"  type="text/javascript"></script>
    </head>

    <body>
        <header>
            <div class="main">
                <h1><a href=".">Phenotyping Sensor Network</a></h1>
            </div>
        </header>

        <main>
            <form id="login-form" method="post" action="../psn-api/login/internal.php">
                <input id="username" name="username" type="text" class="form-control" placeholder="Username"/>
                <input id="password" name="password" type="password" class="form-control" placeholder="Password"/>
                <button type="submit">Log In</button>

                <a href="resources/routines/login.php?type=oauth">Log in with Microsoft Azure</button>
            </form>
        </main>
    </body>
</html>