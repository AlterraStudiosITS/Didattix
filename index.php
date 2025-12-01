<!--<?php
/*require "./private/php/autoload.php";
session_start();

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $conn = getConnection();

    $nome_enc = null;
    $cognome_enc = null;
    $email_enc = null;

    if(strpos($username, ' ') !== false){
        // nome + cognome
        [$nome, $cognome] = explode(' ', $username, 2);
        $nome_enc = encrypt($nome);
        $cognome_enc = encrypt($cognome);
    }else{
        // nome singolo o email
        $nome_enc = encrypt($username);
        $email_enc = encrypt($username);
    }

    $stmt = $conn->prepare("
        SELECT id, nome, cognome, password, user_type, linked_email
        FROM users
        WHERE 
            (nome = ? AND cognome = ?) -- Nome + Cognome
            OR (nome = ? AND cognome IS NULL) -- Solo Nome
            OR (linked_email IS NOT NULL AND linked_email = ?) -- Email
        LIMIT 1
    ");

    $nome_param     = $nome_enc ?? '';
    $cognome_param  = $cognome_enc ?? '';
    $solo_nome      = $nome_enc ?? '';
    $email_param    = $email_enc ?? '';

    $stmt->bind_param("ssss", $nome_param, $cognome_param, $solo_nome, $email_param);

    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows > 0){
        $stmt->bind_result($user_id, $db_nome_enc, $db_cognome_enc, $db_password, $user_type, $linked_email_enc);
        $stmt->fetch();

        if(password_verify($password, $db_password)){
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = decrypt($db_nome_enc) . ($db_cognome_enc ? ' ' . decrypt($db_cognome_enc) : '');
            $_SESSION['user_type'] = $user_type;
            if($linked_email_enc) $_SESSION['linked_email'] = decrypt($linked_email_enc);

            switch($user_type){
                case 'Amministratore':
                    header("Location: public/pages/dashboard/admin/admin.php?user=" . urlencode($user_id));
                    exit;
                case 'Docente':
                    header("Location: public/pages/dashboard/teacher/teacher.php?user=" . urlencode($user_id));
                    exit;
                case 'Studente':
                    header("Location: public/pages/dashboard/student/student.php?user=" . urlencode($user_id));
                    exit;
                default:
                    session_destroy();
                    die("Ruolo utente non riconosciuto.");
            }
        }else $error_message = "Credenziali non valide.";
    }else $error_message = "Credenziali non valide.";

    $stmt->close();
    $conn->close();
}*/
?>-->

<?php
    require "./private/php/autoload.php";

    session_start();

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $conn = getConnection();

        $stmt = null;

        if(strpos($username, ' ') !== false){
            // ➤ nome + cognome
            [$nome, $cognome] = explode(' ', $username, 2);

            $nome_enc = encrypt($nome);
            $cognome_enc = encrypt($cognome);

            $stmt = $conn->prepare("
                SELECT id, nome, password, user_type, linked_email 
                FROM users 
                WHERE nome = ? AND cognome = ?
                LIMIT 1
            ");

            if($stmt) $stmt->bind_param("ss", $nome_enc, $cognome_enc);
        }else{
            $nome_enc = encrypt($username);

            // ➤ First try: nome con cognome NULL
            $stmt = $conn->prepare("
                SELECT id, nome, password, user_type, linked_email 
                FROM users 
                WHERE nome = ? AND cognome IS NULL
                LIMIT 1
            ");

            if($stmt){
                $stmt->bind_param("s", $nome_enc);
                $stmt->execute();
                $stmt->store_result();
                $found = false;

                if($stmt->num_rows > 0) $found = true;

                if(!$found){
                    // ➤ Second try: email
                    $stmt->close();
                    $email_enc = encrypt($username);

                    $stmt = $conn->prepare("
                        SELECT id, nome, password, user_type, linked_email 
                        FROM users 
                        WHERE linked_email IS NOT NULL AND linked_email = ?
                        LIMIT 1
                    ");

                    if($stmt) $stmt->bind_param("s", $email_enc);
                }
            }
        }

        if($stmt){
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($user_id, $db_nome_enc, $db_password, $user_type, $linked_email_enc);
                $stmt->fetch();

                if(password_verify($password, $db_password)){
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = decrypt($db_nome_enc);
                    $_SESSION['user_type'] = $user_type;
                    if($linked_email_enc != null) $_SESSION['linked_email'] = decrypt($linked_email_enc);

                    $role = $_SESSION['user_type'];

                    switch($role){
                        case 'Amministratore':
                            header("Location: public/pages/dashboard/admin/admin.php?user=" . urlencode($user_id));
                            break;
                        case 'Docente':
                            header("Location: public/pages/dashboard/teacher/teacher.php?user=" . urlencode($user_id));
                            break;
                        case 'Studente':
                            header("Location: public/pages/dashboard/student/student.php?user=" . urlencode($user_id));
                            break;
                        default:
                            $error_message = "Ruolo utente non riconosciuto.";
                            session_destroy();
                            exit;
                    }
                }else $error_message = "Credenziali non valide.";
            }else $error_message = "Credenziali non valide.";

            $stmt->close();
        }else $error_message = "Errore nella preparazione della query.";

        $conn->close();
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
            <h2>Effettua il login</h2>
            <div class="box">
                <p class="text-muted">Username o email</p>
                <input type="text" name="username" id="username">
            </div>
            <div class="box">
                <p class="text-muted">Password</p>
                <input type="password" name="password" id="password">
            </div>
            <div class="button">
                <input type="submit" value="Login" class="btn">
            </div>
            <a href="./forgot_password.php"><p>Password dimenticata?</p></a>

            <?php if(isset($error_message)) echo "<p class='error-message'>$error_message</p>"; ?>
        </form>    
    </div>

</body>

<script src="./private/js/app.js"></script>

</html>