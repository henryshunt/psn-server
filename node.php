<?php require_once "php/page-auth.php"; ?>

<meta charset="UTF-8">
<!DOCTYPE html>

<html>
    <head>
        <title>Sensor Node - Phenotyping Sensor Network</title>
        <meta name="viewport" content="width=450px">

        <link href="resources/styles/reset.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/globals.css" rel="stylesheet" type="text/css">
        <link href="https://fonts.googleapis.com/css?family=Istok+Web:400,400i,700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons&display=block" rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" type="text/javascript"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.27/moment-timezone-with-data-10-year-range.min.js" type="text/javascript"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/chartist/0.11.4/chartist.min.css" rel="stylesheet" type="text/css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/chartist/0.11.4/chartist.min.js" type="text/javascript"></script>
        <script src="resources/scripts/config.js.php" type="text/javascript"></script>
        <script src="resources/scripts/helpers.js" type="text/javascript"></script>

        <link href="resources/styles/header.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/header.js" type="text/javascript"></script>
        <link href="resources/styles/grouping.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/pages/node.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/pages/node.js" type="text/javascript"></script>

        <link href="resources/styles/modal.css" rel="stylesheet" type="text/css">


        <script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0" type="text/javascript"></script>


        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
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
            <div class="info-group info-group--hidden" id="node-info-group">
                <div class="info-group__left">
                    <p class="info-group__header">
                        <span class="info-group__title">Sensor Node View: </span>
                        <span class="info-group__sub-title" id="node-location"></span>
                    </p>

                    <p id="node-session"></p>
                    <p id="node-options"></p>
                </div>
            
                <div class="info-group__right">
                    <button class="info-group__action" id="edit-info-btn">View/Edit Info</button>
                    <button class="info-group__action" id="download-data-btn">Download All Data</button>
                    <button class="info-group__action info-group__action--hidden" id="stop-node-btn">Stop Node</button>
                    <button class="info-group__action info-group__action--last" id="delete-node-btn">Delete from Project</button>
                </div>
            </div>

            <div class="time-machine" id="time-machine">
                <button onclick="timeMachineLeft()">
                    <i class="material-icons">chevron_left</i>
                </button>

                <div><span id="time-machine-time">Data for 14/02/2020 at 11:20</span></div>

                <button onclick="timeMachineRight()">
                    <i class="material-icons">chevron_right</i>
                </button>
            </div>

            <div class="titled-group" id="reports-group">
                <div class="titled-group__header">
                    <h2 class="titled-group__title">Reports (Last 6)</h2>
                    <div class="titled-group__separator"></div>
                </div>

                <div class="reports" id="reports"></div>
            </div>

            <div class="titled-group" id="temperature-graph-group">
                <div class="titled-group__header">
                    <h2 class="titled-group__title">Temperature (12 Hours)</h2>
                    <div class="titled-group__separator"></div>
                </div>

                <canvas id="temperature-graph" class="item item-graph"></canvas>
            </div>
        
            <div class="titled-group" id="humidity-graph-group">
                <div class="titled-group__header">
                    <h2 class="titled-group__title">Humidity (12 Hours)</h2>
                    <div class="titled-group__separator"></div>
                </div>

                <canvas id="humidity-graph" class="item item-graph"></canvas>
            </div>

            <div id="alarms-group" class="last-item" style="display: none">
                <div class="titled-group-header">
                    <h2>Alarms</h2>
                    <button id="new-alarm-button" onclick="newAlarmModalOpen()">Add New</button>
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
                    <select id="new-alarm-parameter">
                        <option value="0">Parameter</option>
                        <option value="1">Temperature</option>
                        <option value="2">Humidity</option>
                        <option value="3">Battery Voltage</option>
                    </select>

                    <input id="new-alarm-minimum" type="text" placeholder="Safe Range Minimum"/>
                    <input id="new-alarm-maximum" type="text" placeholder="Safe Range Maximum"/>
                </form>
            </div>

            <div class="modal-footer">
                <button onclick="newAlarmModalSave()">Save</button>
                <button onclick="newAlarmModalClose()">Cancel</button>
            </div>
        </div>
    </body>
</html>