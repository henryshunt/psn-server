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

        <script src="resources/scripts/header.js" type="text/javascript"></script>
        <link href="resources/styles/grouping.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/modal.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/external/bootstrap.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/pages/node.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/pages/node.js" type="text/javascript"></script>
    </head>

    <body>
        <header>
            <div class="main">
                <h1><a href=".">Phenotyping Sensor Network</a></h1>

                <div class="account">
                    <i id="account-button" class="material-icons">settings</i>
                    <span><?php echo $session["user_id"]; ?></span>

                    <div id="account-menu" class="account-menu">
                        <button onclick="logOut()">Log Out</button>
                        <p>Created by Henry Hunt at the University of Nottingham.</p>
                    </div>
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
                        <?php
                        if ($session["user_id"] !== "guest")
                        {
                            echo "<button id=\"button-stop\" onclick=\"stopSessionNodeClick()\">Stop Node Reporting Now</button>";
                            echo "<button class=\"last-item\" onclick=\"deleteSessionNodeClick()\">Delete Node from Session</button>";
                        }
                        ?>
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
        
            <div id="humidity-graph-group" style="display: none">
                <div class="titled-group-header">
                    <h2>Humidity (24 Hours)</h2>
                    <div class="titled-group-separator"></div>
                </div>

                <div id="humidity-graph" class="item item-graph"></div>
            </div>

            <div id="alarms-group" class="last-item" style="display: none">
                <div class="titled-group-header">
                    <h2>Alarms</h2>
                    <?php
                    if ($session["user_id"] !== "guest")
                        echo "<button id=\"new-alarm-button\" onclick=\"newAlarmModalOpen()\">Add New</button>";
                    ?>
                    <div class="titled-group-separator"></div>
                </div>

                <div id="alarms" class="items-block"></div>
            </div>
        </main>

        <div id="modal-shade" class="modal-shade" style="display: none"></div>
        <div id="new-alarm-modal" class="modal" style="display: none">
            <div class="modal-header">
                <span>Create a New Alarm</span>
                <button onclick="newAlarmModalClose()">
                    <i class="material-icons">close</i>
                </button>
            </div>

            <div class="modal-content">
                <p>You will be sent an email when this sensor node reports a value for the specified parameter that is outside of the specified safe range.</p>

                <form>
                    <select id="new-alarm-parameter" class="form-control">
                        <option value="0">Parameter</option>
                        <option value="1">Temperature</option>
                        <option value="2">Humidity</option>
                        <option value="3">Battery Voltage</option>
                    </select>

                    <input id="new-alarm-minimum" type="text" class="form-control" placeholder="Safe Range Minimum"/>
                    <input id="new-alarm-maximum" type="text" class="form-control" placeholder="Safe Range Maximum"/>
                </form>
            </div>

            <div class="modal-footer">
                <button onclick="newAlarmModalSave()">Save</button>
                <button onclick="newAlarmModalClose()">Cancel</button>
            </div>
        </div>
    </body>
</html>