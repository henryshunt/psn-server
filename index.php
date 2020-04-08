<?php
date_default_timezone_set("UTC");
require_once("resources/routines/helpers.php");
require_once("resources/routines/config.php");

$config = new Config();
if (!$config->load_config("config.ini"))
    die ("Configuration error");
$db_connection = database_connection($config);
if (!$db_connection) die ("Database error");

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
        <meta name="viewport" content="width=400px">

        <link href="https://fonts.googleapis.com/css?family=Istok+Web:400,400i,700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons&display=block" rel="stylesheet">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js" type="text/javascript"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" type="text/javascript"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.27/moment-timezone-with-data-10-year-range.min.js" type="text/javascript"></script>
        <link href="resources/styles/globals.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/config.js.php" type="text/javascript"></script>
        <script src="resources/scripts/helpers.js" type="text/javascript"></script>

        <link href="resources/styles/external/bootstrap.css.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/grouping.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/modal.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/pages/index.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/pages/index.js"  type="text/javascript"></script>
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

        <main class="main">
            <div id="active-sessions-group" style="display: none">
                <div class="titled-group-header">
                    <h2>Active Sessions</h2>
                    <button onclick="newSessionModalOpen()">Start New</button>
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

        <div id="modal_shade" class="modal_shade" style="display: none"></div>
        <div id="new_session_modal" class="modal" style="display: none">
            <div class="modal_header">
                <span>Start a New Session</span>
                <button onclick="newSessionModalClose()">
                    <i class="material-icons">close</i>
                </button>
            </div>

            <div class="modal_content">
                <form>
                    <input id="new_session_name" type="text" class="form-control" placeholder="Session Name"/>
                    <textarea id="new_session_description" class="form-control" placeholder="Session Description"></textarea>

                    <div id="session-node-rows"></div>
                    <button style="width: 100%" type="button" onclick="newSessionModalAddNode()">Add Sensor Node</button>
                </form>
            </div>

            <div class="modal_footer">
                <button onclick="newSessionModalSave()">Save & Start</button>
                <button onclick="newSessionModalCancel()">Cancel</button>
            </div>
        </div>
    </body>
</html>