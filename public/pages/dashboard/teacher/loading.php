<?php
require_once "../../../../private/php/autoload.php";
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../../../../index.php");
    exit;
}

$logged_user_id = $_SESSION['user_id'];

if(!isset($_GET['date']) || !isset($_GET['fascia_oraria']) || !isset($_GET['user']) || !isset($_GET['class']) || !isset($_GET['id'])){
    header("Location: teacher.php?error=not-found&user=" . urlencode($logged_user_id));
    exit;
}

$date          = $_GET['date'];
$fascia_oraria = $_GET['fascia_oraria'];
$class_id      = $_GET['class'];
$id            = intval($_GET['id']);

$conn = getConnection();
$stmt = $conn->prepare("DELETE FROM ammonizioni WHERE id = ?");
$stmt->bind_param("i", $id);
$ok = $stmt->execute();
$stmt->close();

$redirect_url = "class.php?date=" . urlencode($date) . "&fascia_oraria=" . urlencode($fascia_oraria) . "&user=" . urlencode($logged_user_id) . "&class=" . urlencode($class_id);

if(!$ok) $redirect_url .= "&error=delete-failed";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <!-- === Redirect === -->
    <meta http-equiv="refresh" content="3;url=<?=$redirect_url?>">

    <!-- === CSS === -->
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #121212;
            color: white;
            font-family: Arial, sans-serif;
        }
        .loader {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: inline-block;
            position: relative;
            border: 3px solid;
            border-color: #FFF #FFF transparent transparent;
            box-sizing: border-box;
            animation: rotation 1s linear infinite;
        }
        .loader::after,
        .loader::before {
            content: '';  
            box-sizing: border-box;
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            margin: auto;
            border: 3px solid;
            border-color: transparent transparent #FF3D00 #FF3D00;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            box-sizing: border-box;
            animation: rotationBack 0.5s linear infinite;
            transform-origin: center center;
        }
        .loader::before {
            width: 32px;
            height: 32px;
            border-color: #FFF #FFF transparent transparent;
            animation: rotation 1.5s linear infinite;
        }
                
        @keyframes rotation {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
            } 
            @keyframes rotationBack {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(-360deg);
            }
        }
    </style>
</head>
<body>
    <span class="loader"></span>
    <div class="msg">Eliminazione in corso... verrai reindirizzato</div>
</body>
</html>