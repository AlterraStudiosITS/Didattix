<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader (created by composer, not included with PHPMailer)
require __DIR__ . '/private/php/mailer/vendor/autoload.php';

require_once './private/php/autoload.php';

$email_error = '';
$email_success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])){
    $email = trim($_POST['email']);

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $email_error = "❌ Email non valida.";
    }else{
        $conn = getConnection();
        if(!$conn) $email_error = "❌ Errore di connessione al database.";
        else{
            $email_enc = encrypt($email);

            $stmt = $conn->prepare("SELECT id, token_expiration FROM users WHERE linked_email = ?");
            $stmt->bind_param("s", $email_enc);
            $stmt->execute();
            $stmt->store_result();

            if($stmt->num_rows === 1){
                $stmt->bind_result($user_id, $token_expiration);
                $stmt->fetch();

                if($token_expiration && strtotime($token_expiration) > time()){
                    $remaining_seconds = strtotime($token_expiration) - time();
                    $remaining_minutes = ceil($remaining_seconds / 60);

                    $email_error = "⏳ Hai già richiesto il reset. Controlla la mail o riprova tra $remaining_minutes minut" . ($remaining_minutes > 1 ? "i" : "o") . ".";
                }else{
                    $token = bin2hex(random_bytes(32));
                    $expiration = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                    $update = $conn->prepare("UPDATE users SET email_verification_token = ?, token_expiration = ? WHERE id = ?");
                    $update->bind_param("ssi", $token, $expiration, $user_id);
                    $update->execute();

                    $reset_link = "http://localhost/didattix/reset_password.php?token=" . urlencode($token);
                    $subject = "Reset della tua password";
                    $htmlBody = "
                        <h2>Reset della tua password</h2>
                        <p>Clicca sul link per creare una nuova password:</p>
                        <p><a href='$reset_link'>$reset_link</a></p>
                        <p>Questo link scadrà tra 15 minuti.</p>
                    ";
                    $plainBody = "Reset della password:\n$reset_link\nScade tra 15 minuti.";

                    $mail = new PHPMailer(true);
                    try{
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'codewithfil@gmail.com';
                        $mail->Password = 'lcwr lbcv qicg hltd';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        $mail->setFrom('noreply@didattix.it', 'Didattix');
                        $mail->addAddress($email);
                        $mail->isHTML(true);
                        $mail->Subject = $subject;
                        $mail->Body = $htmlBody;
                        $mail->AltBody = $plainBody;

                        $mail->send();
                        $email_success = "✅ Ti abbiamo inviato un'email con il link per resettare la password.";
                    }catch(Exception $e){
                        $email_error = "❌ Errore durante l'invio dell'email: {$mail->ErrorInfo}";
                    }
                }
            }else $email_error = "❌ L'email indicata non risulta associata ad alcun account.";

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiDattix | Login</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="shortcut icon" href="./private/images/logo.png">
    <!--<link rel="stylesheet" href="./private/css/style.css">-->
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
        .change-password-container form input[type=text]{
            border: none;
            outline: none;
            border: 1px solid var(--color-light);
            background: transparent;
            height: 2rem;
            width: 100%;
            padding: 0 .5rem;
        }
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
        <form action="" method="POST">
            <h2>Di<span class="danger">Dattix</span></h2>
            <p>Il digitale che fa scuola</p>
            <h2>Inserisci l'email di recupero</h2>

            <?php if ($email_error): ?>
                <p style="color:red"><?= htmlspecialchars($email_error) ?></p>
            <?php elseif ($email_success): ?>
                <p style="color:green"><?= htmlspecialchars($email_success) ?></p>
            <?php endif; ?>

            <div class="box">
                <p class="text-muted">Email</p>
                <input type="text" name="email" id="username">
            </div>
            <div class="button">
                <input type="submit" value="Invia" class="btn">
                <a href="./index.php"><p>Effetua il login</p></a>
            </div>

            <?php if(isset($error_message)) echo "<p class='error-message'>$error_message</p>"; ?>
        </form>    
    </div>

</body>

<script src="./private/js/app.js"></script>

</html>