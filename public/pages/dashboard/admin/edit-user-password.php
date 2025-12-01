<?php
session_start();
require_once "../../../../private/php/autoload.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../../../../index.php");
    exit;
}

$logged_user_id = $_SESSION['user_id'] ?? null;
$user_id = $_GET['user'] ?? null;

$success_message   = "";
$error_message     = "";
$error_currentpass = "";
$error_newpass     = "";
$error_confirmpass = "";

if(!$user_id || !is_numeric($user_id)) $error_message = "ID utente non valido.";
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newpass = $_POST['newpass'] ?? '';
    $confirmpass = $_POST['confirmpass'] ?? '';

    if(empty($newpass))               $error_newpass     = "❌ Inserisci la nuova password.";
    if(empty($confirmpass))           $error_confirmpass = "❌ Conferma la nuova password.";
    if($newpass !== $confirmpass)     $error_confirmpass = "❌ Le due password non coincidono.";
    if(!is_strong_password($newpass)) $error_newpass     = "❌ Password non sicura. Deve contenere almeno:<br>- 8 caratteri<br>- una maiuscola<br>- una minuscola<br>- un numero<br>- un simbolo";
    else{
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();

        if($stmt->num_rows === 1){
            $stmt->bind_result($hashed_password);
            $stmt->fetch();

            $newpass_hashed = password_hash($newpass, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $newpass_hashed, $user_id);

                if($update_stmt->execute()) $success_message = "Password aggiornata con successo.";
                else $error_message = "Errore durante l'aggiornamento della password.";
                $update_stmt->close();
        }else $error_message = "Utente non trovato.";

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiDattix | Cambia password</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="shortcut icon" href="../../../../private/image/logo.png">
    <link rel="stylesheet" href="../../../../private/css/index.css">

    <style>
        header{position: relative;}
        .change-password-container{
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 90vh;
        }
        .change-password-container form{
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-radius: var(--border-radius-2);
            padding : 3.5rem;
            background-color: var(--color-white);
            box-shadow: var(--box-shadow);
            width: 95%;
            max-width: 32rem;
        }
        .change-password-container form:hover{box-shadow: none;}
        .change-password-container form input[type=password]{
            border: none;
            outline: none;
            border: 1px solid var(--color-light);
            background: transparent;
            height: 2rem;
            width: 100%;
            padding: 0 .5rem;
        }
        .change-password-container form .box{
            padding: .5rem 0;
        }
        .change-password-container form .box p{
            line-height: 2;
        }
        .change-password-container form h2+p{margin: .4rem 0 1.2rem 0;} 
        .btn{
            background: none;
            border: none;
            border: 2px solid var(--color-primary) !important;
            border-radius: var(--border-radius-1);
            padding: .5rem 1rem;
            color: var(--color-white);
            background-color: var(--color-primary);
            cursor: pointer;
            margin: 1rem 1.5rem 1rem 0;
            margin-top: 1.5rem;
        }
        .btn:hover{
            color: var(--color-primary);
            background-color: transparent;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="../../../../private/images/logo.png" alt="logo">
            <h2>Di<span class="danger">Dattix</span></h2>
        </div>
        <div class="navbar">
            <a href="admin.php?user=<?= urlencode($logged_user_id) ?>" class="active">
                <span class="material-icons-sharp">home</span>
                <h3>Home</h3>
            </a>
            <a href="admin.php?user=<?= urlencode($logged_user_id) ?>" onclick="timeTableAll()">
                <span class="material-icons-sharp">today</span>
                <h3>Orario</h3>
            </a> 
            <a href="admin.php?user=<?= urlencode($logged_user_id) ?>">
                <span class="material-icons-sharp">grid_view</span>
                <h3>Esami</h3>
            </a>
            <a href="../../password.php?user=<?= urlencode($logged_user_id) ?>">
                <span class="material-icons-sharp">password</span>
                <h3>Cambia password</h3>
            </a>
            <a href="../../../../private/php/sessions/logout.php">
                <span class="material-icons-sharp">logout</span>
                <h3>Esci</h3>
            </a>
        </div>
        <div id="profile-btn" style="display: none;">
            <span class="material-icons-sharp">person</span>
        </div>
        <div class="theme-toggler">
            <span class="material-icons-sharp active">light_mode</span>
            <span class="material-icons-sharp">dark_mode</span>
        </div>
    </header>

    <div class="change-password-container">
        <form method="POST" action="">
            <h2>Resetta password</h2>
            <p class="text-muted">La nuova password deve essere diversa da quella precedente.</p>
            
            <?php if($error_message): ?>
                <p style="color:red;"><?= nl2br(htmlspecialchars($error_message)) ?></p>
            <?php elseif($success_message): ?>
                <p style="color:green;"><?= nl2br(htmlspecialchars($success_message)) ?></p>
            <?php endif; ?>

            <div class="box">
                <?php if($error_newpass): ?>
                    <p style="color:red; font-size: 0.9rem;"><?= $error_newpass ?></p>
                <?php endif; ?>
                <p class="text-muted">Nuova password</p>
                <input type="password" name="newpass">
            </div>
            <div class="box">
                <?php if($error_confirmpass): ?>
                    <p style="color:red; font-size: 0.9rem;"><?= $error_confirmpass ?></p>
                <?php endif; ?>
                <p class="text-muted">Conferma nuova password</p>
                <input type="password" name="confirmpass">
            </div>
            <div class="button">
                <input type="submit" value="Salva" class="btn">
                <a href="admin.php?user=<?= urlencode($user_id); ?>" class="text-muted">Annulla</a>
            </div>
            <a href="#"><p>Password dimenticata?</p></a>
        </form>    
    </div>

</body>

<script src="../../../../private/js/app.js"></script>

</html>