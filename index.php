<?php require_once "php/all-pages.php"; ?>

<meta charset="UTF-8">
<!DOCTYPE html>

<html>
    <head>
        <title>Phenotyping Sensor Network</title>
        <meta name="viewport" content="width=450px">

        <link href="resources/styles/reset.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/globals.css" rel="stylesheet" type="text/css">
        <link href="https://fonts.googleapis.com/css?family=Istok+Web:400,400i,700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons&display=block" rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" type="text/javascript"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.27/moment-timezone-with-data-10-year-range.min.js" type="text/javascript"></script>
        <link href="resources/styles/external/bootstrap.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/config.js.php" type="text/javascript"></script>
        <script src="resources/scripts/helpers.js" type="text/javascript"></script>

        <link href="resources/styles/header.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/header.js" type="text/javascript"></script>
        <link href="resources/styles/grouping.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/modal.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/pages/index.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/pages/index.js"  type="text/javascript"></script>
    </head>

    <body>
        <header>
            <div>
                <div class="main">
                    <h1><a href=".">Phenotyping Sensor Network</a></h1>

                    <div class="user">
                        <i id="user-button" class="material-icons">settings</i>
                        <span><?php echo $session["username"]; ?></span>

                        <div id="user-menu" class="user-menu">
                            <button onclick="logOut()">Log Out</button>
                            <p>Created by Henry Hunt at the University of Nottingham.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="main">
                <div class="special-actions">
                    <a href="special/network.php">Sensor Network Overview</a>
                    <span>&bull;</span>
                    <a href="special/nodes.php">Manage Sensor Nodes</a>
                    <span>&bull;</span>
                    <a href="special/users.php">Manage Users</a>
                </div>
            </div>
        </header>

        <main>
            <div>
                <div class="titled-group-header">
                    <h2>Active Projects</h2>
                    <?php
                    if ($session["userId"] !== "guest")
                        echo "<button onclick=\"newProjectModalOpen()\">Create New</button>";
                    ?>
                    <div class="titled-group-separator"></div>
                </div>

                <div id="active-projects" class="items-block"></div>
            </div>
        
            <div>
                <div class="titled-group-header">
                    <h2>Completed Projects</h2>
                    <div class="titled-group-separator"></div>
                </div>

                <div id="completed-projects" class="items-block"></div>
            </div>
        </main>


        <div id="modal-shade" class="modal-shade"></div>

        <div class="modal new-project-modal" id="new-project-modal">
            <div class="modal-header">
                <span>Create a New Project</span>
            </div>

            <div class="modal-content">
                <form id="new-project-form">
                    <input class="form-control new-project-name" id="new-project-name" type="text" placeholder="Name"/>
                    <textarea class="form-control new-project-description" id="new-project-description" placeholder="Description"></textarea>
                </form>
            </div>

            <div class="modal-footer">
                <span class="modal-status" id="new-project-status"></span>

                <div class="modal-buttons">
                    <button id="new-project-modal-submit" form="new-project-form">Create</button>
                    <button id="new-project-modal-cancel">Cancel</button>
                </div>
            </div>
        </div>
    </body>
</html>