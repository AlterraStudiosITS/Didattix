<?php
session_start();

require_once "../../../../private/php/autoload.php";

$username = $_SESSION['username'] ?? null;
$user_id = $_SESSION['user_id'];

$errors = [];
$success_message = "";

if($_SERVER['REQUEST_METHOD'] === "POST"){
    $conn = null;
    $tipo_utente = $_POST['tipo_utente'] ?? '';
    if($tipo_utente !== "Amministratore"){
        $cognome = trim($_POST['cognome'] ?? '');
        $data_nascita = $_POST['data_nascita'] ?? '';
        $codice_fiscale = $_POST['codice_fiscale'] ?? '';
    }

    $nome = trim($_POST['nome'] ?? '');
    
    $password = $_POST['password'] ?? '';
    $confirmpass = $_POST['confirmpass'] ?? '';

    if($nome === "") $errors['nome'] = "❌ Il campo Nome è obbligatorio.";

    if($tipo_utente !== "Amministratore"){
        if($cognome === "") $errors['cognome']               = "❌ Il campo Cognome è obbligatorio.";
        if($data_nascita === "") $errors['data_nascita']     = "❌ Devi selezionare la data di nascita.";
        if($codice_fiscale === "") $errors['codice_fiscale'] = "❌ Devi inserire il codice fiscale.";

        $conn = getConnection();
        if(!$conn) $errors['db'] = "Errore di connessione al database.";
        else{
            $cf_enc = encrypt($codice_fiscale);
            $cf_stmt = $conn->prepare("SELECT id FROM users WHERE codice_fiscale = ?");
            $cf_stmt->bind_param("s", $cf_enc);
            $cf_stmt->execute();
            $cf_stmt->store_result();

            if($cf_stmt->num_rows > 0) $errors['codice_fiscale'] = "❌ Questo codice fiscale è già associato a un altro utente.";

            $cf_stmt->close();
        }
    }
    if($tipo_utente === "") $errors['tipo_utente']         = "❌ Devi selezionare il tipo di utente.";
    if(!is_strong_password($password)) $errors['password'] = "❌ La password deve avere almeno 8 caratteri, contenere almeno una lettera minuscola, una maiuscola, un numero e un carattere speciale.";
    if($password !== $confirmpass) $errors['confirmpass']  = "❌ Le password non coincidono.";

    if(empty($errors)){
        $conn = getConnection();
        if(!$conn) $errors['db'] = "Errore di connessione al database.";
        else{
            if($tipo_utente !== "Amministratore"){
                $nome_url = strtolower(preg_replace('/[^a-z0-9]/i', '', $nome));
                $cognome_url = strtolower(preg_replace('/[^a-z0-9]/i', '', $cognome));
                $base_url = $nome_url . "_" . $cognome_url;
                $url_address = $base_url . ".didattix.it";

                $counter = 1;
                while(urlExistsInDB($conn, $url_address)){
                    $url_address = $base_url . $counter . ".didattix.it";
                    $counter++;
                }
            }

            $result = null;
            if($tipo_utente === "Amministratore"){
                $result = insertUser(
                    $conn,
                    null,
                    $nome,
                    null,
                    null,
                    null,
                    $tipo_utente,
                    $password,
                    null,
                    null
                );
            }else{
                $result = insertUser(
                    $conn,
                    $url_address,
                    $nome,
                    $cognome,
                    $data_nascita,
                    $codice_fiscale,
                    $tipo_utente,
                    $password,
                    null,
                    null
                );
            }

            if($result['success']) $success_message = "Utente creato con successo.";
            else $errors['insert'] = "Errore durante la creazione dell'utente: " . $result['error'];

            $conn->close();
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
    <link rel="shortcut icon" href="../../../../private/images/logo.png">
    <link rel="stylesheet" href="../../../../private/css/index.css">

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
            <img src="../../../../private/images/logo.png" alt="logo">
            <h2>Di<span class="danger">Dattix</span></h2>
        </div>
        <div class="navbar">
            <a href="admin.php?user=<?= urlencode($user_id) ?>" class="active">
                <span class="material-icons-sharp">home</span>
                <h3>Home</h3>
            </a>
            <a href="admin.php?user=<?= urlencode($user_id) ?>" onclick="timeTableAll()">
                <span class="material-icons-sharp">today</span>
                <h3>Orario</h3>
            </a> 
            <a href="admin.php?user=<?= urlencode($user_id) ?>">
                <span class="material-icons-sharp">grid_view</span>
                <h3>Esami</h3>
            </a>
            <a href="../password.php?user=<?= urlencode($user_id) ?>">
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

    <div class="input-container">
        <form method="POST" action="">
            <h2>Crea nuovo utente</h2>
            <p class="text-muted">Compila i campi sottostanti per creare un nuovo utente.<br>Input con [*] obbligatori</p>
            <?php if(!empty($success_message)): ?>
                <p style="color: green; font-size: 0.95em; margin-top: 10px;"><?= htmlspecialchars($success_message) ?></p>
                <script>
                    setTimeout(function(){
                        window.location.href = "admin.php?user=<?= urlencode($user_id); ?>";
                    }, 1500);
                </script>
            <?php endif; ?>

            <div class="box">
                <?php if(!empty($errors['tipo_utente'])): ?>
                    <p style="color:red; font-size: 0.9em;"><?= htmlspecialchars($errors['tipo_utente']) ?></p>
                <?php endif; ?>

                <p class="text-muted">Tipo di utente *</p>
                <select name="tipo_utente">
                    <option value="" disabled <?= empty($tipo_utente) ? 'selected' : '' ?>>Seleziona tipo utente</option>
                    <option value="Studente" <?= ($tipo_utente ?? '') === 'Studente' ? 'selected' : '' ?>>Studente</option>
                    <option value="Docente" <?= ($tipo_utente ?? '') === 'Docente' ? 'selected' : '' ?>>Docente</option>
                    <option value="Amministratore" <?= ($tipo_utente ?? '') === 'Amministratore' ? 'selected' : '' ?>>Amministratore</option>
                </select>
            </div>
            <div class="box">
                <?php if(!empty($errors['nome'])): ?>
                    <p style="color:red; font-size: 0.9em;"><?= htmlspecialchars($errors['nome']) ?></p>
                <?php endif; ?>

                <p class="text-muted">Nome *</p>
                <input type="text" name="nome" value="<?= htmlspecialchars($nome ?? '') ?>">
            </div>
            <div class="box">
                <?php if(!empty($errors['cognome'])): ?>
                    <p style="color:red; font-size: 0.9em;"><?= htmlspecialchars($errors['cognome']) ?></p>
                <?php endif; ?>

                <p class="text-muted">Cognome *</p>
                <input type="text" name="cognome" value="<?= htmlspecialchars($cognome ?? '') ?>">
            </div>
            <div class="box">
                <?php if(!empty($errors['data_nascita'])): ?>
                    <p style="color:red; font-size: 0.9em;"><?= htmlspecialchars($errors['data_nascita']) ?></p>
                <?php endif; ?>

                <p class="text-muted">Data di nascita *</p>
                <input type="date" name="data_nascita" value="<?= htmlspecialchars($data_nascita ?? '') ?>">
            </div>
            <div class="box">
                <?php if(!empty($errors['codice_fiscale'])): ?>
                    <p style="color:red; font-size: 0.9em;"><?= htmlspecialchars($errors['codice_fiscale']) ?></p>
                <?php endif; ?>

                <p class="text-muted">Codice fiscale *</p>
                <input type="text" name="codice_fiscale" value="<?= htmlspecialchars($codice_fiscale ?? '') ?>">
            </div>
            <div class="box">
                <?php if(!empty($errors['password'])): ?>
                    <p style="color:red; font-size: 0.9em;"><?= htmlspecialchars($errors['password']) ?></p>
                <?php endif; ?>

                <p class="text-muted">Password *</p>
                <input type="password" name="password" value="<?= htmlspecialchars($password ?? '') ?>">
            </div>
            <div class="box">
                <?php if(!empty($errors['confirmpass'])): ?>
                    <p style="color:red; font-size: 0.9em;"><?= htmlspecialchars($errors['confirmpass']) ?></p>
                <?php endif; ?>

                <p class="text-muted">Conferma password *</p>
                <input type="password" name="confirmpass" value="<?= htmlspecialchars($confirmpass ?? '') ?>">
            </div>
            <div class="button">
                <input type="submit" value="Salva" class="btn">
                <a href="admin.php?user=<?= urlencode($user_id) ?>" class="text-muted">Annulla</a>
            </div>
        </form>    
    </div>

</body>

<script src="../../../../private/js/app.js"></script>
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