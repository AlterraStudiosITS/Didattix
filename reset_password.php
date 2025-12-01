<?php
session_start();
require_once "./private/php/autoload.php";

$token = $_GET['token'] ?? null;

$success_message   = "";
$error_message     = "";
$error_newpass     = "";
$error_confirmpass = "";

if(!$token || strlen($token) !== 64) header("Location: ./verification/verification_denied.php?token=" . $token);
else{
    $conn = getConnection();

    $stmt = $conn->prepare("SELECT id, token_expiration FROM users WHERE email_verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows === 1){
        $stmt->bind_result($user_id, $token_expiration);
        $stmt->fetch();

        if(strtotime($token_expiration) < time()) $error_message = "❌ Il link è scaduto. Richiedi un nuovo reset.";
    }else header("Location: ./verification/verification_denied.php?token=" . $token);
    $stmt->close();
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message)){
    $newpass = $_POST['newpass'] ?? '';
    $confirmpass = $_POST['confirmpass'] ?? '';

    if(empty($newpass)) $error_newpass = "❌ Inserisci la nuova password.";
    if(empty($confirmpass)) $error_confirmpass = "❌ Conferma la nuova password.";
    if($newpass !== $confirmpass) $error_confirmpass = "❌ Le due password non coincidono.";
    if(!is_strong_password($newpass)) $error_newpass = "❌ Password non sicura. Deve contenere almeno:<br>- 8 caratteri<br>- una maiuscola<br>- una minuscola<br>- un numero<br>- un simbolo";

    if(empty($error_newpass) && empty($error_confirmpass)){
        $newpass_hashed = password_hash($newpass, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE users SET password = ?, email_verification_token = NULL, token_expiration = NULL WHERE id = ?");
        $update->bind_param("si", $newpass_hashed, $user_id);

        if($update->execute()){
            $success_message = "✅ Password aggiornata con successo. Ora puoi accedere.";
            echo "<script>
                    setTimeout(function(){
                        window.location.href = 'index.php';
                    }, 1500);
                </script>";
        }else{
            $error_message = "❌ Errore durante l'aggiornamento della password.";
        }
        $update->close();
    }

    $conn->close();
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
    <link rel="shortcut icon" href="./private/image/logo.png">
    <link rel="stylesheet" href="./private/css/index.css">

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
            <img src="./private/images/logo.png" alt="logo">
            <h2>Di<span class="danger">Dattix</span></h2>
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

<script src="./private/js/app.js"></script>

</html>