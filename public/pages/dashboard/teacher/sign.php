<?php
session_start();
require_once "../../../../private/php/autoload.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../../../../index.php");
    exit;
}

$logged_user_id = $_SESSION['user_id'] ?? null;

if(!isset($_GET['class'])){
    header("Location: ../../../../tacher.php?error=not-found&user=" . urlencode($logged_user_id));
    exit;
}

$class_id = $_GET['class'];

$date = DateTime::createFromFormat('d-m-Y', $_GET['date']);
if($date) $date = $date->format('Y-m-d'); 
else $date = date('Y-m-d');

$fascia_oraria = $_GET['fascia_oraria'];

$error_message = '';
$success_message = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $description = trim($_POST['description'] ?? '');

    if(strlen($description) < 10) $error_message = "❌ La descrizione deve contenere almeno 10 caratteri.";
    else{
        $conn = getConnection();

        $sql_students = "SELECT user_id FROM studenti_classi WHERE classe_id = ?";
        $stmt = $conn->prepare($sql_students);
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while($row = $result->fetch_assoc()){
            $user_id_studente = $row['user_id'];

            $sql_insert = "INSERT INTO presenze 
                (user_id, classe_id, teacher_id_signature, signature_description, data, fascia_oraria, stato)
                VALUES (?, ?, ?, ?, ?, ?, 'P')
                ON DUPLICATE KEY UPDATE 
                    teacher_id_signature = VALUES(teacher_id_signature),
                    signature_description = VALUES(signature_description),
                    stato = 'P'";
            
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iiisss",
                $user_id_studente,
                $class_id,
                $logged_user_id,
                $description,
                $date,
                $fascia_oraria
            );
            $stmt_insert->execute();
        }

        $conn->close();

        $success_message = "Ora di lezione firmata con successo.";
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
        .change-password-container form .box textarea{
            background: var(--color-light);
            resize: none;
            width: 100%;
            height: 5rem;
            border: none;
            border-radius: var(--border-radius-1);
        }
        .change-password-container form .box textarea:focus {
            outline: none;
            box-shadow: none;
        }
        .change-password-container form .box textarea::-webkit-scrollbar {
            width: 8px;
        }

        .change-password-container form .box textarea::-webkit-scrollbar-track {
            background: var(--color-background);
            border-radius: 10px;
        }

        .change-password-container form .box textarea::-webkit-scrollbar-thumb {
            background: var(--color-background);
            border-radius: 10px;
            border: 2px solid var(--color-light);
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
            <a href="teacher.php?user=<?= urlencode($logged_user_id) ?>" class="active">
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
            <h2>Firma ora di lezione</h2>
            
            <?php if($error_message): ?>
                <p style="color:red;"><?= nl2br(htmlspecialchars($error_message)) ?></p>
            <?php elseif($success_message): ?>
                <p style="color:green;"><?= nl2br(htmlspecialchars($success_message)) ?></p>
            <?php endif; ?>

            <div class="box">
                <p class="text-muted">Argomento / attività svolta</p>
                <textarea name="description" rows="3"></textarea>
            </div>
            <div class="button">
                <input type="submit" value="Salva" class="btn">
                <a href="class.php?user=<?= urlencode($logged_user_id); ?>&class=<?= urlencode($class_id) ?>" class="text-muted">Annulla</a>
            </div>
        </form>    
    </div>

</body>

<script src="../../../../private/js/app.js"></script>

</html>