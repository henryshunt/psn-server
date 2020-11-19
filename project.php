<?php require_once "php/page-auth.php"; ?>

<meta charset="UTF-8">
<!DOCTYPE html>

<html>
    <head>
        <title>Project - Phenotyping Sensor Network</title>
        <meta name="viewport" content="width=450px">

        <link href="resources/styles/reset.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/globals.css" rel="stylesheet" type="text/css">
        <link href="https://fonts.googleapis.com/css?family=Istok+Web:400,400i,700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons&display=block" rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" type="text/javascript"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.27/moment-timezone-with-data-10-year-range.min.js" type="text/javascript"></script>
        <script src="resources/scripts/config.js.php" type="text/javascript"></script>
        <script src="resources/scripts/helpers.js" type="text/javascript"></script>

        <link href="resources/styles/header.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/header.js" type="text/javascript"></script>
        <link href="resources/styles/grouping.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/pages/project.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/pages/project.js"  type="text/javascript"></script>

        <link href="resources/styles/modal.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/modals/edit-project.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/modals/edit-project.js" type="text/javascript"></script>
        <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.3/dist/flatpickr.min.css" rel="stylesheet" type="text/css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.3/dist/flatpickr.min.js" type="text/javascript"></script>
        <link href="resources/styles/modals/add-node.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/modals/add-node.js" type="text/javascript"></script>
    </head>

    <body>
        <header class="header">
            <div class="header__first-row">
                <div class="main">
                    <h1 class="header__title">
                        <a href=".">Phenotyping Sensor Network</a>
                    </h1>

                    <div class="user">
                        <i class="user__open-menu material-icons" id="user-menu-btn">settings</i>

                        <p class="user__name">
                            <?php echo $user["username"]; ?>
                        </p>

                        <div class="user__menu user__menu--hidden" id="user-menu">
                            <button class="user__log-out" id="log-out-btn">Log Out</button>
                            <p class="user__attribution">Created by Henry Hunt at the University of Nottingham.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="header__second-row main">
                <div class="actions">
                    <a class="actions__anchor" href="special/network.php">Sensor Network Overview</a>
                    <span>&bull;</span>
                    <a class="actions__anchor" href="special/nodes.php">Manage Sensor Nodes</a>
                    <span>&bull;</span>
                    <a class="actions__anchor" href="special/users.php">Manage Users</a>
                </div>
            </div>
        </header>

        <main id="main">
            <div class="info-group info-group--hidden" id="project-info-group">
                <div class="info-group__left">
                    <p class="info-group__header">
                        <span class="info-group__title">Project View: </span>
                        <span class="info-group__sub-title" id="project-name"></span>
                    </p>

                    <p class="info-group__body" id="project-desc"></p>
                </div>
            
                <div class="info-group__right">
                    <button class="info-group__action" id="edit-project-btn">Edit Project Info</button>
                    <button class="info-group__action" id="download-data-btn">Download All Data</button>
                    <button class="info-group__action info-group__action--hidden" id="stop-project-btn">Stop Project</button>
                    <button class="info-group__action info-group__action--last" id="delete-project-btn">Delete Project</button>
                </div>
            </div>

            <div class="titled-group titled-group--hidden" id="active-nodes-group">
                <div class="titled-group__header">
                    <h2 class="titled-group__title">Active Sensor Nodes</h2>
                    <button class="titled-group__action" id="add-node-btn">Add Node</button>
                    <div class="titled-group__separator"></div>
                </div>

                <div class="items-block" id="active-nodes"></div>
            </div>

            <div class="titled-group titled-group--hidden" id="completed-nodes-group">
                <div class="titled-group__header">
                    <h2 class="titled-group__title">Completed Sensor Nodes</h2>
                    <div class="titled-group__separator"></div>
                </div>

                <div class="items-block" id="completed-nodes"></div>
            </div>
        </main>

        <div class="modal__shade modal__shade--hidden" id="modal-shade"></div>
        <?php include_once "resources/modals/edit-project.inc"; ?>
        <?php include_once "resources/modals/add-node.inc"; ?>
    </body>
</html>