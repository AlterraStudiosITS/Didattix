<?php
session_start();
require_once "../../../../private/php/autoload.php";

if(!isset($_GET['student']) || !isset($_GET['class']) || !isset($_GET['date']) || !isset($_GET['materia'])){
    header("Location: teacher.php?error=not-found&user=" . $logged_user_id);
    exit;
}

$selected_materia = $_GET['materia'] ?? '';
$selected_tipo = $_GET['tipo'] ?? '';
$logged_user_id = $_SESSION['user_id'] ?? null;

if(!isset($_GET['student']) || !isset($_GET['class']) || !isset($_GET['date'])){
    header("Location: teacher.php?error=not-found&user=" . $logged_user_id);
    exit;
}

$error_message   = '';
$success_message = '';

$class_id   = $_GET['class'];
$student_id = $_GET['student'];
$date       = $_GET['date'];

$conn = getConnection();

$stmt = $conn->prepare("SELECT nome, cognome FROM users WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

$student_nome    = $student ? decrypt($student['nome']) : 'Studente';
$student_cognome = $student ? decrypt($student['cognome']) : 'Sconosciuto';

$stmt = $conn->prepare("SELECT materia FROM docenti_classi WHERE user_id = ? AND classe_id = ?");
$stmt->bind_param("ii", $logged_user_id, $class_id);
$stmt->execute();
$result = $stmt->get_result();

$materie_docente = [];
while($row = $result->fetch_assoc()){
    $materie_docente[] = decrypt($row['materia']);
}
$stmt->close();

$date_input = $_GET['date'] ?? '';
$date_obj = DateTime::createFromFormat('d-m-Y', $date_input);
if(!$date_obj) $date_obj = DateTime::createFromFormat('d/m/Y', $date_input);
if(!$date_obj) $date_obj = new DateTime();
$date = $date_obj->format('Y-m-d');

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $materia     = $_POST['materia'] ?? '';
    $tipo        = $_POST['tipo'] ?? 'Altro';
    $voto        = floatval($_POST['voto'] ?? 0);
    $descrizione = trim($_POST['descrizione'] ?? '');

    if(empty($materia) || $voto <= 0) $error_message = "❌ Tutti i campi obbligatori devono essere compilati.";
    elseif(!in_array($materia, $materie_docente)){
        $error_message = "❌ Materia non valida o non assegnata a questa classe.";
    }
    else{
        $materia_enc = encrypt($materia);
        $descrizione_enc = encrypt($descrizione);

        $stmt = $conn->prepare("INSERT INTO valutazioni (user_id, classe_id, teacher_id, materia, voto, descrizione, data, tipo)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisdsss", $student_id, $class_id, $logged_user_id, $materia_enc, $voto, $descrizione_enc, $date, $tipo);

        if($stmt->execute()){
            $success_message = "✔ Valutazione registrata correttamente.";
            header("refresh:3;url=class.php?date=" . urlencode($date) . "&user=" . urlencode($logged_user_id) . "&class=" . urlencode($class_id));
        }else $error_message = "Errore nel salvataggio: " . $stmt->error;

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiDattix | Valutazione</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="shortcut icon" href="../../../../private/image/logo.png">
    <link rel="stylesheet" href="../../../../private/css/index.css">
    <style>
        header{position: relative;}

        .change-password-container{display:flex;align-items:center;justify-content:center;width:100%;height:90vh;}
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
        .change-password-container form input,
        .change-password-container form select{
            border:1px solid var(--color-light);background:transparent;height:2rem;width:100%;padding:0 .5rem;
            border-radius:var(--border-radius-1);
        }
    
        .change-password-container form select option{
            color: var(--color-dark);
            background: var(--color-white);
        }

        .change-password-container form textarea{
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
        <a href="../../password.php?user=<?= urlencode($logged_user_id) ?>">
            <span class="material-icons-sharp">password</span>
            <h3>Cambia password</h3>
        </a>
        <a href="../../../../private/php/sessions/logout.php">
            <span class="material-icons-sharp">logout</span>
            <h3>Esci</h3>
        </a>
    </div>
    <div id="profile-btn">
        <span class="material-icons-sharp">person</span>
    </div>
    <div class="theme-toggler">
        <span class="material-icons-sharp active">light_mode</span>
        <span class="material-icons-sharp">dark_mode</span>
    </div>
</header>

<div class="change-password-container">
    <form method="POST" action="">
        <h2><?= htmlspecialchars($student_nome) . " " . htmlspecialchars($student_cognome) ?> | Valutazione</h2>

        <?php if($error_message): ?>
            <p style="color:red;"><?= nl2br(htmlspecialchars($error_message)) ?></p>
        <?php elseif($success_message): ?>
            <p style="color:green;"><?= nl2br(htmlspecialchars($success_message)) ?></p>
        <?php endif; ?>

        <div class="box">
            <p>Materia *</p>
            <select name="materia" required>
                <option value="">Seleziona una materia</option>
                <?php if(!empty($materie_docente)): ?>
                    <?php foreach ($materie_docente as $m): 
                        $selected = ($m === $selected_materia) ? 'selected' : '';
                    ?>
                        <option value="<?= htmlspecialchars($m) ?>" <?= $selected ?>><?= htmlspecialchars($m) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="">(Nessuna materia assegnata a questa classe)</option>
                <?php endif; ?>
            </select>
        </div>
        <div class="box">
            <p>Tipo di voto *</p>
            <select name="tipo">
                <?php 
                $tipi = ['Orale', 'Scritto', 'Pratico', 'Altro'];
                foreach ($tipi as $t) {
                    $selected = ($t === $selected_tipo) ? 'selected' : '';
                    echo "<option value=\"$t\" $selected>$t</option>";
                }
                ?>
            </select>
        </div>

        <div class="box">
            <p>Voto *</p>
            <select name="voto" required>
                <option value="0">Non classificato</option>
                <?php 
                    for($v = 1; $v <= 10; $v += 0.25) echo "<option value='".number_format($v,2)."'>".number_format($v,2)."</option>";
                ?>
            </select>
        </div>

        <div class="box">
            <p>Descrizione</p>
            <textarea name="descrizione" placeholder="Note..."></textarea>
        </div>

        <div class="button">
            <input type="submit" value="Salva" class="btn">
            <a href="grades.php?user=<?= urlencode($logged_user_id); ?>&class=<?= urlencode($class_id) ?>?&date=<?= urlencode($date) ?>" class="text-muted">Annulla</a>
        </div>
    </form>
</div>

<script src="../../../../private/js/app.js"></script>
</body>
</html>
