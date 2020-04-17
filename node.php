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
        <link href="https://cdnjs.cloudflare.com/ajax/libs/chartist/0.11.4/chartist.min.css" rel="stylesheet" type="text/css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/chartist/0.11.4/chartist.min.js" type="text/javascript"></script>
        <link href="resources/styles/globals.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/config.js.php" type="text/javascript"></script>
        <script src="resources/scripts/helpers.js" type="text/javascript"></script>

        <link href="resources/styles/grouping.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/pages/node.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/pages/node.js" type="text/javascript"></script>
    </head>

    <body>
        <header>
            <div class="main">
                <h1><a href=".">Phenotyping Sensor Network</a></h1>

                <div class="account">
                    <i class="material-icons">settings</i>
                    
                    <span>
                        <?php
                        // If the user ID contains an @, only display the part before the @
                        if (strpos($session["user_id"], "@") !== FALSE)
                            echo substr($session["user_id"], 0, strpos($session["user_id"], "@"));
                        else echo $session["user_id"];
                        ?>
                    </span>
                </div>
            </div>
        </header>

        <main id="main" class="main">
            <div id="node-info-group" style="display: none">
                <div class="info-group">
                    <div class="info-group-left">
                        <span>
                            <span>Node View</span><span> | </span>
                            <span id="node-location"></span>
                        </span>

                        <br>
                        <span id="node-session"></span>
                        <br>
                        <p id="node-options"></p>
                    </div>
                
                    <div class="info-group-right">
                        <button onclick="downloadDataClick()">Download All Data</button>
                        <button id="button-stop" onclick="stopSessionNodeClick()">Stop Node Reporting Now</button>
                        <button class="last-item" onclick="deleteSessionNodeClick()">Delete Node from Session</button>
                    </div>
                </div>
            </div>

            <div class="time-machine" id="time-machine" style="display: none">
                <button onclick="timeMachineLeft()">
                    <i class="material-icons">chevron_left</i>
                </button>

                <div><span id="time-machine-time">Data for 14/02/2020 at 11:20</span></div>

                <button onclick="timeMachineRight()">
                    <i class="material-icons">chevron_right</i>
                </button>
            </div>

            <div id="reports-group" style="display: none">
                <div class="titled-group-header">
                    <h2>Reports (Last 6)</h2>
                    <div class="titled-group-separator"></div>
                </div>

                <div class="reports" id="reports"></div>
            </div>

            <div id="temperature-graph-group" style="display: none">
                <div class="titled-group-header">
                    <h2>Temperature (24 Hours)</h2>
                    <div class="titled-group-separator"></div>
                </div>

                <div id="temperature-graph" class="item item-graph"></div>
            </div>
        
            <div id="humidity-graph-group" class="last-item" style="display: none">
                <div class="titled-group-header">
                    <h2>Humidity (24 Hours)</h2>
                    <div class="titled-group-separator"></div>
                </div>

                <div id="humidity-graph" class="item item-graph">
            </div>
        </main>
    </body>
</html>