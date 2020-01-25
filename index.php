<?php include_once("resources/routines/index.php"); ?>

<meta charset="UTF-8">
<!DOCTYPE html>

<html>
    <head>
        <title>Phenotyping Sensor Network</title>
        <meta name="viewport" content="width=400px">

        <link href="resources/styles/index.css" rel="stylesheet" type="text/css">
        <link href="https://fonts.googleapis.com/css?family=Istok+Web:400,400i,700&display=swap" rel="stylesheet">
    </head>

    <body>
        <div class="header">
            <div class="main">
                <h1 class="header_title">Phenotyping Sensor Network</h1>
            </div>
        </div>

        <div class="main">
            <div class="group_header">
                <h2 class="group_title">Active Sessions</h2>
                <div class="group_button">Create New</div>
                <div class="group_separator"></div>
            </div>

            <div class="group_content">
                <?php echo $active_sessions_html; ?>
            </div>
        
            <div class="group_header">
                <h2 class="group_title">Completed Sessions</h2>
                <div class="group_separator"></div>
            </div>

            <div class="group_content">
                <?php echo $completed_sessions_html; ?>
            </div>

            <div class="group_header">
                <h2 class="group_title">Alarms</h2>
                <div class="group_button">Create New</div>
                <div class="group_separator"></div>
            </div>

            <div class="group_content">
                <?php echo $active_alarms_html; ?>
            </div>
        </div>
    </body>
</html>