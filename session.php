<?php include_once("resources/routines/session.php"); ?>

<meta charset="UTF-8">
<!DOCTYPE html>

<html>
    <head>
        <title>Phenotyping Sensor Network</title>
        <meta name="viewport" content="width=400px">

        <link href="resources/styles/globals.css" rel="stylesheet" type="text/css">
        <link href="https://fonts.googleapis.com/css?family=Istok+Web:400,400i,700&display=swap" rel="stylesheet">
        <link href="resources/styles/grouping.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/session.css" rel="stylesheet" type="text/css">
    </head>

    <body>
        <div class="header">
            <div class="main">
                <h1 class="header_title">Phenotyping Sensor Network</h1>
            </div>
        </div>

        <div class="main">
            <?php echo $session_info_html; ?>
            
            <div class="group_header">
                <h2 class="group_title">Active Sensor Nodes</h2>
                <div class="button group_button">Add New</div>
                <div class="group_separator"></div>
            </div>

            <div class="group_content">
                <?php echo $active_nodes_html; ?>
            </div>
        
            <div class="group_header">
                <h2 class="group_title">Completed Sensor Nodes</h2>
                <div class="group_separator"></div>
            </div>

            <div class="group_content">
                <?php echo $completed_nodes_html; ?>
            </div>
        </div>
    </body>
</html>