<?php
session_start();
require_once "../../../private/php/autoload.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../../../index.php");
    exit;
}

$username     = $_SESSION['username'] ?? null;
$user_id      = $_SESSION['user_id'];
$user_type    = $_SESSION['user_type'];
$linked_email = $_SESSION['linked_email'] ?? 'Nessuna email associata';

$email_error = '';
$email_success = '';

if(isset($_POST['submit_email'])){
    $email = trim($_POST['email'] ?? '');

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $email_error = "❌ Email non valida.";
    else{
        $conn = getConnection();
        if(!$conn) $email_error = "❌ Errore di connessione al database.";
        else{
            $email_enc = encrypt($email);

            $self_check_stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND linked_email = ?");
            $self_check_stmt->bind_param("is", $user_id, $email_enc);
            $self_check_stmt->execute();
            $self_check_stmt->store_result();

            if($self_check_stmt->num_rows > 0)$email_error = "[!] Questa email è già associata al tuo account.";
            else{
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE linked_email = ? AND id != ?");
                $check_stmt->bind_param("si", $email_enc, $user_id);
                $check_stmt->execute();
                $check_stmt->store_result();

                if($check_stmt->num_rows > 0) $email_error = "❌ Questa email è già associata a un altro utente.";
                else{
                    $_SESSION['linked_email'] = $email;
                    $user_email = $email;

                    $result = updateEmail($conn, $user_id, $email);
                    if(!$result['success']) $email_error = "❌ " . $result['error'];
                    else $email_success = "✅ Ti abbiamo inviato una mail di verifica, controlla la casella di posta.";
                }

                $check_stmt->close();
            }

            $self_check_stmt->close();
        }
    }
}

$success_message = "";
$error_message = "";

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_password'])){
    $currentpass = $_POST['currentpass'] ?? '';
    $newpass = $_POST['newpass'] ?? '';
    $confirmpass = $_POST['confirmpass'] ?? '';

    if(!$username){
        $error_message = "Utente non autenticato.";
    }elseif(empty($currentpass) || empty($newpass) || empty($confirmpass)){
        $error_message = "Compila tutti i campi.";
    }elseif($newpass !== $confirmpass){
        $error_message = "Le due password non coincidono.";
    }else if(!is_strong_password($newpass)){
        $error_message = "Password non sicura. " .
                 "La nuova password deve contenere almeno:\n" .
                 "- 8 caratteri\n" .
                 "- una lettera maiuscola\n" .
                 "- una lettera minuscola\n" .
                 "- un numero\n" .
                 "- un carattere speciale";
    }else{
        $conn = getConnection();
        $username_enc = encrypt($username);
        $stmt = $conn->prepare("SELECT password FROM users WHERE nome = ? LIMIT 1");
        $stmt->bind_param("s", $username_enc);
        $stmt->execute();
        $stmt->store_result();

        if($stmt->num_rows == 1){
            $stmt->bind_result($hashed_password);
            $stmt->fetch();

            if(!password_verify($currentpass, $hashed_password)) $error_message = "La password attuale non è corretta.";
            elseif(password_verify($newpass, $hashed_password)) $error_message = "La nuova password deve essere diversa da quella attuale.";
            else{
                $newpass_hashed = password_hash($newpass, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE nome = ?");
                $update_stmt->bind_param("ss", $newpass_hashed, $username_enc);

                if($update_stmt->execute()){
                    if($user_type === 'Studente'){
                        header("Location: ./student/student.php?user=" . urlencode($user_id));
                        exit;
                    }elseif($user_type === 'Docente'){
                        header("Location: ./teacher/teacher.php?user=" . urlencode($user_id));
                        exit;
                    }elseif($user_type === 'Amministratore' . urlencode($user_id)){
                        header("Location: ./admin/admin.php?user=" . urlencode($user_id));
                        exit;
                    }else $success_message = "Password aggiornata; ruolo utente sconosciuto.";
                }else $error_message = "Errore nell'aggiornamento della password.";

                $update_stmt->close();
            }
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
    <link rel="shortcut icon" href="../../../private/images/logo.png">
    <link rel="stylesheet" href="../../../private/css/index.css">

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
        .change-password-container form input[type=password],
        .change-password-container form input[type=text]{
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
            <img src="../../../private/images/logo.png" alt="logo">
            <h2>Di<span class="danger">Dattix</span></h2>
        </div>
        <div class="navbar">
            <?php
            $home_link = "index.html";

            if($user_type === 'Studente') $home_link = "./student/student.php?user=" . urlencode($user_id);
            elseif($user_type === 'Docente') $home_link = "./teacher/teacher.php?user=" . urlencode($user_id);
            elseif($user_type === 'Amministratore') $home_link = "./admin/admin.php?user=" . urlencode($user_id);
            ?>

            <a href="<?= htmlspecialchars($home_link) ?>">
                <span class="material-icons-sharp">home</span>
                <h3>Home</h3>
            </a>
            <a href="<?= htmlspecialchars($home_link) ?>" onclick="timeTableAll()">
                <span class="material-icons-sharp">today</span>
                <h3>Orario</h3>
            </a> 
            <a href="<?= htmlspecialchars($home_link) ?>">
                <span class="material-icons-sharp">grid_view</span>
                <h3>Esami</h3>
            </a>
            <a href="password.php?user?<?= urlencode($user_id) ?>" class="active">
                <span class="material-icons-sharp">password</span>
                <h3>Cambia password</h3>
            </a>
            <a href="../../../private/php/sessions/logout.php">
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
            <!-- ==== EMAIL ==== -->
            <h2>Associa email</h2>
            <p class="text-muted">Inserisci un'email da associare al tuo account.</p>
            <?php if ($email_error): ?>
                <p style="color:red;"><?= htmlspecialchars($email_error) ?></p>
            <?php elseif ($email_success): ?>
                <p style="color:green;"><?= htmlspecialchars($email_success) ?></p>
            <?php endif; ?>

            <div class="box">
                <p class="text-muted">Email</p>
                <input type="text" name="email" value="<?= htmlspecialchars($user_email ?? '') ?>" placeholder="<?= $linked_email ?>">
            </div>
            <div class="button">
                <input type="submit" name="submit_email" value="Salva" class="btn">
            </div>

            <!-- ==== PASSWORD ==== -->
            <br><br>
            <h2>Crea nuova password</h2>
            <p class="text-muted">La nuova password deve essere diversa da quella precedente.</p>
            
            <?php if($error_message): ?>
                <p style="color:red;"><?= nl2br(htmlspecialchars($error_message)) ?></p>
            <?php elseif($success_message): ?>
                <p style="color:green;"><?= nl2br(htmlspecialchars($success_message)) ?></p>
            <?php endif; ?>

            <div class="box">
                <p class="text-muted">Password attuale</p>
                <input type="password" name="currentpass">
            </div>
            <div class="box">
                <p class="text-muted">Nuova password</p>
                <input type="password" name="newpass">
            </div>
            <div class="box">
                <p class="text-muted">Conferma nuova password</p>
                <input type="password" name="confirmpass">
            </div>
            <div class="button">
                <input type="submit" name="submit_password" value="Salva" class="btn">
                <a href="<?= htmlspecialchars($home_link) ?>" class="text-muted">Annulla</a>
            </div>
        </form>    
    </div>

</body>

<script src="../../../private/js/app.js"></script>

</html>