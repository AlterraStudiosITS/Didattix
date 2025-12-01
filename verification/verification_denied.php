<?php?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiDattix | Login</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="shortcut icon" href="../private/images/logo.png">
    <!--<link rel="stylesheet" href="./private/css/style.css">-->
    <link rel="stylesheet" href="../private/css/index.css">

    <style>
        header{
            position: relative;
        }
        .change-password-container{
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 90vh;
        }
        .expired-box{
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-radius: var(--border-radius-2);
            padding: 3.5rem;
            background-color: var(--color-white);
            box-shadow: var(--box-shadow);
            width: 95%;
            max-width: 32rem;
            text-align: center;
        }
        .expired-box:hover{
            box-shadow: none;
        }
        .expired-box h2{
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .expired-box img{
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 1.2rem;
        }
        .expired-box img{
            width: 70px;
            height: auto;
        }
        .expired-box p{
            font-size: 1.4rem;
        }
        .expired-box a{
            font-size: 1.1rem;
        }
    </style>

</head>
<body>
    <header>
        <div class="logo">
            <img src="../private/images/logo.png" alt="logo">
            <h2>Di<span class="danger">Dattix</span></h2>
        </div>
        <div class="navbar">
        <div id="profile-btn" style="display: none;">
            <span class="material-icons-sharp">person</span>
        </div>
        <div class="theme-toggler">
            <span class="material-icons-sharp active">light_mode</span>
            <span class="material-icons-sharp">dark_mode</span>
        </div>
    </header>

    <div class="change-password-container">
        <div class="expired-box">
            <h2>Di<span class="danger">Dattix</span></h2>
            <img src="../private/images/x-red.png" alt="Error">
            <p>Questa pagina è scaduta</p><br>
            <a href="../index.php">⟵ Torna indietro</a>
        </div>
    </div>

</body>

<script src="../private/js/app.js"></script>

</html>