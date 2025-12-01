<?php
session_start();
require_once "../../../../../private/php/autoload.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../../../../../index.php");
    exit;
}

if(!isset($_GET['user']) || empty($_GET['user'])){
    header('Location: ../admin.php');
    exit;
}

$user_id  = $_GET['user'];
$class_id = $_GET['class'];

$conn = getConnection();

$existingUserIds = [];
$res = mysqli_query($conn, "SELECT user_id FROM studenti_classi WHERE classe_id = $class_id");
while($row = mysqli_fetch_assoc($res)) $existingUserIds[] = intval($row['user_id']);

$exclude = "";
if(!empty($existingUserIds)) $exclude = "AND id NOT IN (" . implode(',', $existingUserIds) . ")";

$query = "SELECT id, nome, cognome, linked_email FROM users WHERE user_type = 'Studente' $exclude";
$result = mysqli_query($conn, $query);

if (!$result) die("Errore nella query: " . mysqli_error($conn));
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiDattix | Cambia password</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="shortcut icon" href="../../../../../private/images/logo.png">
    <link rel="stylesheet" href="../../../../../private/css/index.css">

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

        /* ==== Select user container ==== */
        .user-selection{
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            border-radius: var(--border-radius-2);
            padding: 3.5rem;
            background-color: var(--color-white);
            box-shadow: var(--box-shadow);
            width: 95%;
            max-width: 50rem;
            height: 70vh;
            overflow-y: auto;
        }
        .user-list{
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1rem;
        }
        .user-box{
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid var(--color-light);
            border-radius: var(--border-radius-1);
            background-color: var(--color-background);
            transition: background 0.3s ease;
        }
        .user-box:hover{
            background-color: var(--color-light);
        }
        .styled-checkbox{
            appearance: none;
            width: 1.2rem;
            height: 1.2rem;
            border: 2px solid var(--color-primary);
            border-radius: 4px;
            cursor: pointer;
            position: relative;
            transition: background 0.2s ease;
        }
        .styled-checkbox:checked{
            background-color: var(--color-primary);
        }
        .styled-checkbox:checked::after{
            content: '';
            position: absolute;
            top: 2px;
            left: 4px;
            width: 4px;
            height: 8px;
            border: solid var(--color-white);
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        .user-box label{
            line-height: 1.4;
            cursor: pointer;
            user-select: none;
        }

        .user-selection::-webkit-scrollbar{
            width: 12px;
        }
        .user-selection::-webkit-scrollbar-track{
            background: var(--color-white);
            border-radius: 8px;
        }
        .user-selection::-webkit-scrollbar-thumb{
            background-color: var(--color-primary);
            border-radius: 8px;
            border: 2px solid var(--color-white);
        }
        .user-selection::-webkit-scrollbar-thumb:hover{
            background-color: #2a5aaa;
        }
    </style>

</head>
<body>
    <header>
        <div class="logo">
            <img src="../../../../../private/images/logo.png" alt="logo">
            <h2>Di<span class="danger">Dattix</span></h2>
        </div>
        <div class="navbar">
            <a href="../admin.php?user=<?= urlencode($user_id) ?>" class="active">
                <span class="material-icons-sharp">home</span>
                <h3>Home</h3>
            </a>
            <a href="../admin.php?user=<?= urlencode($user_id) ?>" onclick="timeTableAll()">
                <span class="material-icons-sharp">today</span>
                <h3>Orario</h3>
            </a> 
            <a href="../admin.php?user=<?= urlencode($user_id) ?>">
                <span class="material-icons-sharp">grid_view</span>
                <h3>Esami</h3>
            </a>
            <a href="../../password.php?user?<?= urlencode($user_id) ?>">
                <span class="material-icons-sharp">password</span>
                <h3>Cambia password</h3>
            </a>
            <a href="../../../../../private/php/sessions/logout.php">
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

    <form method="POST" action="../../../../../private/php/sessions/save-students.php?user=<?= urlencode($user_id) ?>&class=<?= urlencode($class_id) ?>" class=<?= urlencode($class_id) ?>>
        <div class="change-password-container">
            <div class="user-selection">
                <h2>Seleziona studenti</h2>
                <p class="text-muted">Spunta gli studenti che vuoi aggiungere alla classe.</p>

                <div class="user-list">
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <?php
                            $nome = decrypt($row['nome']);
                            $cognome = decrypt($row['cognome']);
                            $email = $row['linked_email'] !== null ? decrypt($row['linked_email']) : "non associata";
                        ?>
                        <div class="user-box">
                            <input type="checkbox" 
                                id="user<?= htmlspecialchars($row['id']) ?>" 
                                name="users[]" 
                                value="<?= htmlspecialchars($row['id']) ?>" 
                                class="styled-checkbox">
                            <label for="user<?= htmlspecialchars($row['id']) ?>">
                                <strong><?= htmlspecialchars($nome . ' ' . $cognome) ?></strong><br>
                                <span class="text-muted">Email: <?= htmlspecialchars($email) ?></span>
                            </label>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="button">
                    <input type="submit" name="submit_users" value="Aggiungi studenti" class="btn">
                    <a href="edit-class.php?user=<?= urlencode($user_id) ?>&class=<?= urlencode($class_id) ?>" class="text-muted">Annulla</a>
                </div>
            </div>
        </div>
    </form>

</body>

<script src="../../../../../private/js/app.js"></script>

</html>