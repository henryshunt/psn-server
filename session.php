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
if ($session === NULL)
{
    header("Location: login.php");
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

        <script src="resources/scripts/header.js" type="text/javascript"></script>
        <link href="resources/styles/grouping.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/pages/session.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/pages/session.js" type="text/javascript"></script>
    </head>

    <body>
        <header>
            <div class="main">
                <h1><a href=".">Phenotyping Sensor Network</a></h1>

                <div class="account">
                    <i id="account-button" class="material-icons">settings</i>

                    <span>
                        <?php
                        // If the user ID contains an @, only display the part before the @
                        if (strpos($session["user_id"], "@") !== FALSE)
                            echo substr($session["user_id"], 0, strpos($session["user_id"], "@"));
                        else echo $session["user_id"];
                        ?>
                    </span>
                </div>

                <div id="account-menu" class="account-menu">
                    <button onclick="logOut()">Log Out</button>
                    <p>Created by Henry Hunt at the University of Nottingham.</p>
                </div>
            </div>
        </header>

        <main id="main" class="main">
            <div id="session-info-group" style="display: none">
                <div class="info-group">
                    <div class="info-group-left">
                        <span>
                            <span>Session View</span><span> | </span>
                            <span id="session-name"></span>
                        </span>
                        <br>
                        <span id="session-description"></span>
                    </div>
                
                    <div class="info-group-right">
                        <button>Download All Data</button>
                        <button id="button-stop" onclick="stopSessionNow()">Stop Session Now</button>
                        <button class="last-item" id="button-delete" onclick="deleteSessionClick()">Delete Session</button>
                    </div>
                </div>
            </div>

            <div id="active-nodes-group" style="display: none">
                <div class="titled-group-header">
                    <h2>Active Sensor Nodes</h2>
                    <div class="titled-group-separator"></div>
                </div>

                <div id="active-nodes" class="items-block"></div>
            </div>
        
            <div id="completed-nodes-group" style="display: none">
                <div class="titled-group-header">
                    <h2>Completed Sensor Nodes</h2>
                    <div class="titled-group-separator"></div>
                </div>

                <div id="completed-nodes" class="items-block">
            </div>
        </main>
    </body>
</html>