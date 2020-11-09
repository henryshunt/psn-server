<?php
require_once "php/helpers.php";

$config = load_configuration("config.json");
if ($config === false)
    die("Configuration error");

try
{
    $pdo = database_connect($config["databaseHost"], $config["databaseName"],
        $config["databaseUsername"], $config["databasePassword"]);
}
catch (Exception $ex)
{
    die("Database error");
}

if (isset($_COOKIE["session"]))
{
    $session = get_login_session($_COOKIE["session"], $pdo);

    if ($session === false)
        die("Session error");
}
else $session = null;

if ($session === null)
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
        <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.3/dist/flatpickr.min.css" rel="stylesheet" type="text/css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.3/dist/flatpickr.min.js" type="text/javascript"></script>
        <link href="resources/styles/grouping.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/modal.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/external/bootstrap.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/pages/index.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/pages/index.js"  type="text/javascript"></script>
    </head>

    <body>
        <header>
            <div class="main">
                <h1><a href=".">Phenotyping Sensor Network</a></h1>

                <div class="account">
                    <i id="account-button" class="material-icons">settings</i>
                    <span><?php echo $session["username"]; ?></span>

                    <div id="account-menu" class="account-menu">
                        <button onclick="logOut()">Log Out</button>
                        <?php
                        if ($session["username"] === "admin")
                            echo "<button onclick=\"newNodeModalOpen()\">Add New Sensor Node</button>";
                        ?>
                        <p>Created by Henry Hunt at the University of Nottingham.</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="main">
            <div id="active-sessions-group" style="display: none">
                <div class="titled-group-header">
                    <h2>Active Sessions</h2>
                    <?php
                    if ($session["user_id"] !== "guest")
                        echo "<button onclick=\"newSessionModalOpen()\">Start New</button>";
                    ?>
                    <div class="titled-group-separator"></div>
                </div>

                <div id="active-sessions" class="items-block"></div>
            </div>
        
            <div id="completed-sessions-group" style="display: none">
                <div class="titled-group-header">
                    <h2>Completed Sessions</h2>
                    <div class="titled-group-separator"></div>
                </div>

                <div id="completed-sessions" class="items-block"></div>
            </div>
        </main>

        <div id="modal-shade" class="modal-shade" style="display: none"></div>
        <div id="new-session-modal" class="modal" style="display: none">
            <div class="modal-header">
                <span>Start a New Session</span>
                <button onclick="newSessionModalClose()">
                    <i class="material-icons">close</i>
                </button>
            </div>

            <div class="modal-content">
                <form>
                    <input id="new-session-name" type="text" class="form-control" placeholder="Session Name"/>
                    <textarea id="new-session-description" class="form-control" placeholder="Session Description"></textarea>

                    <div id="session-node-rows" class="session-node-rows"></div>
                    <button id="session-node-add-button" type="button" onclick="newSessionModalAddNode()" disabled>Add Sensor Node</button>
                </form>
            </div>

            <div class="modal-footer">
                <button onclick="newSessionModalSave()">Save & Start</button>
                <button onclick="newSessionModalClose()">Cancel</button>
            </div>
        </div>

        <div id="new-node-modal" class="modal" style="display: none">
            <div class="modal-header">
                <span>Add a New Sensor Node to the Network</span>
                <button onclick="newNodeModalClose()">
                    <i class="material-icons">close</i>
                </button>
            </div>

            <div class="modal-content">
                <p>Enter the MAC address of the sensor node exactly as displayed at the top of the node administrator program.</p>

                <form>
                    <input id="new-node-address" type="text" class="form-control" placeholder="Sensor Node MAC Address"/>
                </form>
            </div>

            <div class="modal-footer">
                <button onclick="newNodeModalSave()">Save</button>
                <button onclick="newNodeModalClose()">Cancel</button>
            </div>
        </div>
    </body>
</html>