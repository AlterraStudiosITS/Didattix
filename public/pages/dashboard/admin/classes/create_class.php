<?php
session_start();

require_once "../../../../../private/php/autoload.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../../../../../index.php");
    exit;
}

$username = $_SESSION['username'] ?? null;
$user_id = $_SESSION['user_id'];

$current_year = date('Y');
$next_year = $current_year + 1;
$placeholder_year = "$current_year/$next_year";

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nome            = trim($_POST['nome'] ?? '');
    $course          = trim($_POST['course'] ?? '');
    $anno_scolastico = trim($_POST['anno_scolastico'] ?? '');

    $errors = [];

    if(empty($nome)) $errors['nome']                       = '❌ Il nome è obbligatorio.';
    if(empty($anno_scolastico)) $errors['anno_scolastico'] = '❌ L\'anno scolastico è obbligatorio.';
    if(empty($course)) $errors['course']                   = '❌ L\'indirizzo di studio è obbligatorio.';

    if(empty($errors)){   
        if(classExists($nome, $course)) $errors['course'] = '❌ Una classe risulta già esistente con questo nome e indirizzo di studio.';
        else{
            $result = createClass($nome, $course, $anno_scolastico);

            if($result['success']){
                $success_message = "La classe è stata creata con successo!";
                unset($nome, $anno_scolastico, $course);
            }else $errors['general'] = "Errore: " . htmlspecialchars($result['error']);
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
    <title>DiDattix | Cambia password</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="shortcut icon" href="../../../../../private/images/logo.png">
    <link rel="stylesheet" href="../../../../../private/css/index.css">

    <style>
        header{position: relative;}
        .input-container{
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 90vh;
        }
        .input-container form{
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
        .input-container form:hover{box-shadow: none;}
        .input-container form input[type=text]{
            border: none;
            outline: none;
            border: 1px solid var(--color-light);
            background: transparent;
            height: 2rem;
            width: 100%;
            padding: 0 .5rem;
        }
        .input-container form input[type=date]{
            border: none;
            outline: none;
            border: 1px solid var(--color-light);
            background: transparent;
            height: 2rem;
            width: 100%;
            padding: 0 .5rem;
        }
        .input-container form input[type=password]{
            border: none;
            outline: none;
            border: 1px solid var(--color-light);
            background: transparent;
            height: 2rem;
            width: 100%;
            padding: 0 .5rem;
        }
        .input-container form select{
            border: none;
            outline: none;
            border: 1px solid var(--color-light);
            background: transparent;
            height: 2.4rem;
            width: 100%;
            padding: 0 0.5rem;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
            color: var(--color-dark, #000);
            border-radius: 0.25rem;
            background-image: url("data:image/svg+xml;utf8,<svg fill='gray' height='16' viewBox='0 0 24 24' width='16' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1em;
        }
        body.dark-theme .input-container form select{
            border: 1px solid var(--color-light-dark, #555);
            background-color: #222;
            color: #eee;
            background-image: url("data:image/svg+xml;utf8,<svg fill='lightgray' height='16' viewBox='0 0 24 24' width='16' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>"); /* freccia più chiara */
        }

        body.dark-theme .input-container form select option{
            background-color: #222;
            color: #eee;
        }

        .input-container form select:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
            outline: none;
        }
        .input-container form .box{
            padding: .5rem 0;
        }
        .input-container form .box p{
            line-height: 2;
        }
        .input-container form h2+p{margin: .4rem 0 1.2rem 0;} 
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
            <a href="../../password.php?user=<?= urlencode($user_id) ?>">
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

    <div class="input-container">
        <form method="POST" action="">
            <h2>Crea nuova classe</h2>
            <p class="text-muted">Compila i campi sottostanti per creare una nuova classe.<br>Input con [*] obbligatori</p>
            <?php if(!empty($success_message)): ?>
                <p style="color: green; font-size: 0.95em; margin-top: 10px;"><?= htmlspecialchars($success_message) ?></p>
                <script>
                    setTimeout(function(){
                        window.location.href = "../admin.php?user=<?= urlencode($user_id); ?>";
                    }, 1500);
                </script>
            <?php endif; ?>

            <div class="box">
                <?php if(!empty($errors['nome'])): ?>
                    <p style="color:red; font-size: 0.9em;"><?= htmlspecialchars($errors['nome']) ?></p>
                <?php endif; ?>

                <p class="text-muted">Nome classe *</p>
                <input type="text" name="nome" value="<?= htmlspecialchars($nome ?? '') ?>">
            </div>
            <div class="box">
                <?php if(!empty($errors['anno_scolastico'])): ?>
                    <p style="color:red; font-size: 0.9em;"><?= htmlspecialchars($errors['anno_scolastico']) ?></p>
                <?php endif; ?>

                <p class="text-muted">Anno scolastico *</p>
                <input type="text" name="anno_scolastico" placeholder="<?= $placeholder_year ?>" value="<?= htmlspecialchars($anno_scolastico ?? '') ?>">
            </div>
            <div class="box">
                <?php if(!empty($errors['course'])): ?>
                    <p style="color:red; font-size: 0.9em;"><?= htmlspecialchars($errors['course']) ?></p>
                <?php endif; ?>

                <p class="text-muted">Indirizzo di studio *</p>
                <select name="course">
                    <option value="" disabled <?= empty($course) ? 'selected' : '' ?>>Seleziona indirizzo di studio</option>
                    <option value="Chimica, Materiali e Biotecnologie" <?= ($course ?? '') === 'Chimica, Materiali e Biotecnologie' ? 'selected' : '' ?>>Chimica, Materiali e Biotecnologie</option>
                    <option value="Elettronica ed elettrotecnica" <?= ($course ?? '') === 'Elettronica ed elettrotecnica' ? 'selected' : '' ?>>Elettronica ed elettrotecnica</option>
                    <option value="Informatica e Telecomunicazioni" <?= ($course ?? '') === 'Informatica e Telecomunicazioni' ? 'selected' : '' ?>>Informatica e Telecomunicazioni</option>
                    <option value="Meccanica, Meccatronica ed Energia" <?= ($course ?? '') === 'Meccanica, Meccatronica ed Energia' ? 'selected' : '' ?>>Meccanica, Meccatronica ed Energia</option>
                    <option value="Grafica e comunicazione" <?= ($course ?? '') === 'Grafica e comunicazione' ? 'selected' : '' ?>>Grafica e comunicazione</option>
                    <option value="Grafica sportiva" <?= ($course ?? '') === 'Grafica sportiva' ? 'selected' : '' ?>>Grafica sportiva</option>
                </select>
            </div>
            <div class="button">
                <input type="submit" value="Salva" class="btn">
                <a href="admin.php?user=<?= urlencode($user_id) ?>" class="text-muted">Annulla</a>
            </div>
            <a href="#"><p>Password dimenticata?</p></a>
        </form>    
    </div>

</body>

<script src="../../../../../private/js/app.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const tipoUtente = document.querySelector("select[name='tipo_utente']");
        const fieldsToToggle = ["cognome", "data_nascita", "codice_fiscale"];

        const toggleFields = () => {
            const isAdmin = tipoUtente.value === "Amministratore";
            fieldsToToggle.forEach(field => {
                const box = document.querySelector(`input[name='${field}']`)?.closest(".box");
                if(box) box.style.display = isAdmin ? "none" : "block";
            });
        };

        tipoUtente.addEventListener("change", toggleFields);
        toggleFields();
    });
</script>

</html>