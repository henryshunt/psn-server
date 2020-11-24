<?php
const LOGIN_PAGE = true;
require_once "php/page-auth.php";
?>

<meta charset="UTF-8">
<!DOCTYPE html>

<html>
    <head>
        <title>Log In - Phenotyping Sensor Network</title>
        <meta name="viewport" content="width=450px">

        <link href="resources/styles/reset.css" rel="stylesheet" type="text/css">
        <link href="resources/styles/globals.css" rel="stylesheet" type="text/css">
        <link href="https://fonts.googleapis.com/css?family=Istok+Web:400,400i,700&display=swap" rel="stylesheet">
        <script src="resources/scripts/config.js.php" type="text/javascript"></script>
        <script src="resources/scripts/helpers.js" type="text/javascript"></script>

        <link href="resources/styles/header.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/header.js" type="text/javascript"></script>
        <link href="resources/styles/pages/login.css" rel="stylesheet" type="text/css">
        <script src="resources/scripts/pages/login.js"  type="text/javascript"></script>
    </head>

    <body>
        <header class="header">
            <div class="header__first-row">
                <div class="main">
                    <h1 class="header__title">
                        <a href=".">Phenotyping Sensor Network</a>
                    </h1>
                </div>
            </div>
        </header>

        <main>
            <div>
                <form class="form" id="login-form" method="post" action="auth/internal.php">
                    <input class="form__username" id="username" name="username" type="text" placeholder="Username"/>
                    <input class="form__password" id="password" name="password" type="password" placeholder="Password"/>
                    <button class="form__log-in" type="submit">Log In</button>
                </form>

                <div class="or">
                    <div class="or__line"></div>
                    <div class="or__text">or</div>
                </div>

                <form action="login/oauth.php">
                    <button class="form__log-in" type="submit">Log in with University Credentials</button>
                </form>
            </div>
        </main>
    </body>
</html>