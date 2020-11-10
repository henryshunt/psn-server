<?php require_once "php/all-pages.php"; ?>

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
                    <span><?php echo $session["user_id"]; ?></span>

                    <div id="account-menu" class="account-menu">
                        <button onclick="logOut()">Log Out</button>
                        <p>Created by Henry Hunt at the University of Nottingham.</p>
                    </div>
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
                        <button onclick="downloadDataClick()">Download All Data</button>
                        <?php
                        if ($session["user_id"] !== "guest")
                        {
                            echo "<button id=\"button-stop1\" onclick=\"stopSessionNow()\">Stop Session Now</button>";
                            echo "<button class=\"last-item\" id=\"button-delete\" onclick=\"deleteSessionClick()\">Delete Session</button>";
                        }
                        ?>
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