<?php
session_start();

require_once "../../../../../private/php/autoload.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../../../index.php");
    exit;
}

if(!isset($_GET['user']) && !isset($_GET['class'])){
    header("Location: ../admin.php?error=id-not-found&user=" . urlencode($user_id));
    exit;
}

$logged_user_id = $_GET['user'];
$class_id       = $_GET['class'];

$errors = [];
$success_message = "";

$docenti = [];
$conn = getConnection();
if($conn){
    $sql_docenti = "SELECT id, nome, cognome FROM users WHERE user_type = 'Docente' ORDER BY cognome, nome";
    $result_docenti = $conn->query($sql_docenti);
    if($result_docenti && $result_docenti->num_rows > 0){
        while ($row = $result_docenti->fetch_assoc()) $docenti[] = $row;
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $teacher_id = $_POST['teacher_id'] ?? '';
    $subject    = $_POST['subject'] ?? '';
    $role       = $_POST['role'] ?? '';

    if($teacher_id === '') $errors['teacher_id'] = "❌ Devi selezionare un docente.";
    if($subject === '')    $errors['subject']    = "❌ Devi selezionare una materia.";
    if($role === '')       $errors['role']       = "❌ Devi selezionare un ruolo.";

    if(empty($errors)){
        $conn = getConnection();
        if(!$conn) $errors['db'] = "Errore di connessione al database.";
        else{
            $stmt_check = $conn->prepare("SELECT id FROM docenti_classi WHERE docente_id = ? AND classe_id = ? AND materia = ?");
            $stmt_check->bind_param("iis", $teacher_id, $class_id, $subject);
            $stmt_check->execute();
            $stmt_check->store_result();

            if($stmt_check->num_rows > 0) $errors['exists'] = "❌ Questo docente insegna già questa materia in questa classe.";

            $stmt_check->close();

            if(empty($errors)){
                $stmt_insert = $conn->prepare("INSERT INTO docenti_classi (docente_id, classe_id, materia, ruolo) VALUES (?, ?, ?, ?)");
                $stmt_insert->bind_param("iiss", $teacher_id, $class_id, $subject, $role);
                $result = $stmt_insert->execute();

                if($result) $success_message = "✅ Docente assegnato correttamente alla classe.";
                else $errors['insert'] = "❌ Errore durante l'inserimento: " . $stmt_insert->error;

                $stmt_insert->close();
            }

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
        <form method="POST" action="../../../../../private/php/sessions/save-teacher.php?user=<?= urlencode($logged_user_id) ?>&class=<?= urlencode($class_id) ?>">
            <h2>Aggiungi un insegnante alla classe</h2>
            <p class="text-muted">Compila tutti i campi per completare il processo. Input con [*] obbligatori</p>

            <?php if(!empty($success_message)): ?>
                <p style="color: green; font-size: 0.95em;"><?= htmlspecialchars($success_message) ?></p>
                <script>
                    setTimeout(() => {
                        window.location.href = "../admin.php?user=<?= urlencode($logged_user_id); ?>";
                    }, 1500);
                </script>
            <?php endif; ?>

            <?php if(!empty($errors['exists'])): ?>
                <p style="color:red; font-size: 0.9em;"><?= htmlspecialchars($errors['exists']) ?></p>
            <?php endif; ?>

            <div class="box">
                <?php if(!empty($errors['teacher_id'])): ?>
                    <p style="color:red; font-size: 0.9em;"><?= htmlspecialchars($errors['teacher_id']) ?></p>
                <?php endif; ?>
                <p class="text-muted">Docente *</p>
                <select name="teacher_id">
                    <option value="" disabled <?= empty($_POST['teacher_id']) ? 'selected' : '' ?>>Seleziona docente</option>
                    <?php foreach($docenti as $docente): ?>
                        <option value="<?= $docente['id'] ?>" <?= (isset($_POST['teacher_id']) && $_POST['teacher_id'] == $docente['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(decrypt($docente['nome']) . ' ' . decrypt($docente['cognome'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="box">
                <?php if(!empty($errors['subject'])): ?>
                <p style="color:red; font-size: 0.9em;"><?= htmlspecialchars($errors['subject']) ?></p>
            <?php endif; ?>
            <p class="text-muted">Materia *</p>
            <select name="subject">
                <option value="" disabled <?= empty($subject) ? 'selected' : '' ?>>Seleziona materia</option>

                <!-- Materie comuni -->
                <option value="Italiano" <?= ($subject ?? '') === 'Italiano' ? 'selected' : '' ?>>Italiano</option>
                <option value="Matematica" <?= ($subject ?? '') === 'Matematica' ? 'selected' : '' ?>>Matematica</option>
                <option value="Inglese" <?= ($subject ?? '') === 'Inglese' ? 'selected' : '' ?>>Inglese</option>
                <option value="Storia" <?= ($subject ?? '') === 'Storia' ? 'selected' : '' ?>>Storia</option>
                <option value="Geografia" <?= ($subject ?? '') === 'Geografia' ? 'selected' : '' ?>>Geografia</option>
                <option value="Educazione civica" <?= ($subject ?? '') === 'Educazione civica' ? 'selected' : '' ?>>Educazione civica</option>
                <option value="Scienze motorie" <?= ($subject ?? '') === 'Scienze motorie' ? 'selected' : '' ?>>Scienze motorie</option>
                <option value="Religione" <?= ($subject ?? '') === 'Religione' ? 'selected' : '' ?>>Religione</option>

                <!-- Materie scientifiche/tecniche -->
                <option value="Fisica" <?= ($subject ?? '') === 'Fisica' ? 'selected' : '' ?>>Fisica</option>
                <option value="Chimica" <?= ($subject ?? '') === 'Chimica' ? 'selected' : '' ?>>Chimica</option>
                <option value="Tecnologia" <?= ($subject ?? '') === 'Tecnologia' ? 'selected' : '' ?>>Tecnologia</option>

                <!-- Materie tecnico-professionali -->
                <option value="Informatica" <?= ($subject ?? '') === 'Informatica' ? 'selected' : '' ?>>Informatica</option>
                <option value="Telecomunicazioni" <?= ($subject ?? '') === 'Telecomunicazioni' ? 'selected' : '' ?>>Telecomunicazioni</option>
                <option value="Sistemi e reti" <?= ($subject ?? '') === 'Sistemi e reti' ? 'selected' : '' ?>>Sistemi e reti</option>
                <option value="Tecnologie e progettazione di sistemi informatici e di telecomunicazioni" <?= ($subject ?? '') === 'Tecnologie e progettazione di sistemi informatici e di telecomunicazioni' ? 'selected' : '' ?>>Tecnologie e progettazione di sistemi informatici e di telecomunicazioni</option>
                <option value="Elettronica ed Elettrotecnica" <?= ($subject ?? '') === 'Elettronica ed Elettrotecnica' ? 'selected' : '' ?>>Elettronica ed Elettrotecnica</option>
                <option value="Meccanica e macchine" <?= ($subject ?? '') === 'Meccanica e macchine' ? 'selected' : '' ?>>Meccanica e macchine</option>
                <option value="Disegno tecnico" <?= ($subject ?? '') === 'Disegno tecnico' ? 'selected' : '' ?>>Disegno tecnico</option>
                <option value="Automazione" <?= ($subject ?? '') === 'Automazione' ? 'selected' : '' ?>>Automazione</option>
                <option value="Gestione progetto, organizzazione del lavoro" <?= ($subject ?? '') === 'Gestione progetto, organizzazione del lavoro' ? 'selected' : '' ?>>Gestione progetto, organizzazione del lavoro</option>

                <!-- Materie economico-giuridiche -->
                <option value="Diritto ed economia" <?= ($subject ?? '') === 'Diritto ed economia' ? 'selected' : '' ?>>Diritto ed economia</option>
                <option value="Economia aziendale" <?= ($subject ?? '') === 'Economia aziendale' ? 'selected' : '' ?>>Economia aziendale</option>
            </select>
            </div>

            <div class="box">
                <?php if(!empty($errors['role'])): ?>
                    <p style="color:red; font-size: 0.9em;"><?= htmlspecialchars($errors['role']) ?></p>
                <?php endif; ?>
                <p class="text-muted">Ruolo *</p>
                <select name="role">
                    <option value="" disabled <?= empty($role) ? 'selected' : '' ?>>Seleziona ruolo</option>
                    <option value="titolare" <?= ($role ?? '') === 'titolare' ? 'selected' : '' ?>>Titolare</option>
                    <option value="supplente" <?= ($role ?? '') === 'supplente' ? 'selected' : '' ?>>Supplente</option>
                    <option value="sostegno" <?= ($role ?? '') === 'sostegno' ? 'selected' : '' ?>>Sostegno</option>
                    <option value="assistente" <?= ($role ?? '') === 'assistente' ? 'selected' : '' ?>>Assistente</option>
                    <option value="tutor" <?= ($role ?? '') === 'tutor' ? 'selected' : '' ?>>Tutor</option>
                    <option value="referente" <?= ($role ?? '') === 'referente' ? 'selected' : '' ?>>Referente</option>
                </select>
            </div>
            
            <div class="button">
                <input type="submit" value="Assegna" class="btn">
                <a href="edit-class.php?user=<?= urlencode($logged_user_id); ?>&class=<?= urlencode($class_id) ?>" class="text-muted">Annulla</a>
            </div>
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