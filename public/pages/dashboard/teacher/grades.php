<?php
require_once "../../../../private/php/autoload.php";
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../../../../index.php");
    exit;
}

$logged_user_id = $_SESSION['user_id'];

if(!isset($_GET['date']) || !isset($_GET['user']) || !isset($_GET['class']) || !isset($_GET['fascia_oraria'])){
    header("Location: teacher.php?error=not-found&user=" . urlencode($logged_user_id));
    exit;
}

$max_voti_per_type = 2;
$fascia_oraria = $_GET['fascia_oraria'];
$date          = $_GET['date'];
$class_id      = $_GET['class'];
$conn          = getConnection();

/* ───────────────────────────────
   🔹 Recupera la classe asseegnata al docente
   ─────────────────────────────── */
$stmt = $conn->prepare("SELECT nome_classe, sezione, anno_scolastico FROM classi WHERE id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();
$class = $result->fetch_assoc();
$stmt->close();

if(!$class){
    header("Location: teacher.php?error=class-not-found&user=" . urlencode($logged_user_id));
    exit;
}

$class_nome = htmlspecialchars($class['nome_classe']);
$class_sezione = htmlspecialchars($class['sezione']);
$class_anno = htmlspecialchars($class['anno_scolastico']);

/* ───────────────────────────────
   🔹 Recupera le materie del docente per quella classe
   ─────────────────────────────── */
$stmt = $conn->prepare("SELECT materia FROM docenti_classi WHERE user_id = ? AND classe_id = ?");
$stmt->bind_param("ii", $logged_user_id, $class_id);
$stmt->execute();
$result = $stmt->get_result();
$materie = [];
$tipi = [];
while($row = $result->fetch_assoc()){
    $materie[] = decrypt($row['materia']);
    $tipi = ['Orale', 'Scritto', 'Pratico', 'Altro'];
}
$stmt->close();
/* ───────────────────────────────
   🔹 Recupera tutti gli studenti della classe
   ─────────────────────────────── */
$stmt = $conn->prepare("SELECT u.id, u.nome, u.cognome
                        FROM studenti_classi sc
                        INNER JOIN users u ON sc.user_id = u.id
                        WHERE sc.classe_id = ?
                        ORDER BY u.cognome, u.nome");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();
$studenti = [];
while($row = $result->fetch_assoc()){
    $studenti[$row['id']] = decrypt($row['cognome']) . ' ' . decrypt($row['nome']);
}
$stmt->close();

/* ───────────────────────────────
   🔹 Recupera le valutazioni inserite dal docente per quella classe
   ─────────────────────────────── */
$stmt = $conn->prepare("SELECT id, user_id, materia, voto, tipo
                        FROM valutazioni
                        WHERE classe_id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $class_id, $logged_user_id);
$stmt->execute();
$result = $stmt->get_result();

$valutazioni = [];
while($row = $result->fetch_assoc()){
    $gradeId = $row['id'];
    $materia = decrypt($row['materia']);
    $tipo = $row['tipo'];
    
    $voto = rtrim(rtrim($row['voto'], '0'), '.');
    
    $valutazioni[$row['user_id']][$materia][$tipo][] = [
        'id' => $gradeId,
        'voto' => $voto
    ];
}
$stmt->close();
$conn->close();

/* ───────────────────────────────
   🔹 Elenca tutti i voti per ogni studente/materia
   ─────────────────────────────── */
$matrix = [];
foreach($studenti as $studId => $studName){
    foreach($materie as $mat){
        foreach($tipi as $tipo){
            $voti = $valutazioni[$studId][$mat][$tipo] ?? [];
            $voti = array_slice($voti, 0, $max_voti_per_type);

            while(count($voti) < $max_voti_per_type) $voti[] = '-';

            $matrix[$studId][$mat][$tipo] = $voti;
        }
    }
}

function calcolaMedia($matrix, $studId, $materia){
    $somma = 0;
    $count = 0;

    foreach(['Orale', 'Scritto', 'Pratico', 'Altro'] as $tipo){
        $voti = $matrix[$studId][$materia][$tipo] ?? [];

        foreach($voti as $voto){
            if(is_numeric($voto['voto']) && $voto['voto'] !== '-'){
                $somma += (float)$voto['voto'];
                $count++;
            }
        }
    }

    return ($count > 0) ? round($somma / $count, 2) : '-';
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Didattix | Valutazioni</title>
    <link rel="shortcut icon" href="../../../../private/images/logo.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="../../../../private/css/style.css">

    <style>
    .good-grade,
    .failing-grade{
        display: inline-block;
        padding: 4px 8px;
        border-radius: 10px;
        font-weight: 600;
        text-align: center;
        min-width: 32px;
        transition: 0.2s;
    }

    .good-grade{
        background-color: #155724;
        color: #d4edda;
    }

    .failing-grade{
        background-color: #721c24;
        color: #f8d7da;
    }
    </style>
</head>
<body>
    <header>
        <div class="logo" title="DiDattix">
            <img src="../../../../private/images/logo.png" alt="logo">
            <h2>Di<span class="danger">Dattix</span></h2>
        </div>
        <div class="navbar">
            <a href="student.php?user=<?= urlencode($logged_user_id) ?>" class="active">
                <span class="material-icons-sharp">home</span>
                <h3>Home</h3>
            </a>
            <a href="../password.php?user=<?= urlencode($logged_user_id) ?>">
                <span class="material-icons-sharp">password</span>
                <h3>Resetta password</h3>
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

    <div class="container">
        <aside>
            <div class="profile">
                <div class="top">
                    <div class="profile-photo">
                        <img src="../../../../private/images/user-icon.png" alt="user">
                    </div>
                    <div class="info">
                        <p>Ciao, <b><?= htmlspecialchars($_SESSION['username'] ?? 'Utente') ?></b></p>
                        <small class="text-muted">12102030</small>
                    </div>
                </div>
                <div class="about">
                    <h5>Corsi</h5>
                    <p>BTech. Computer Science & Engineering</p>
                    <h5>DOB</h5>
                    <p>29-Feb-2020</p>
                    <h5>Contatti</h5>
                    <p>1234567890</p>
                    <h5>Email</h5>
                    <p>unknown@gmail.com</p>
                    <h5>Indirizzo</h5>
                    <p></p>
                </div>
            </div>
        </aside>
        <main>
            <h1><a href="class.php?date=<?= urlencode($date) ?>&user=<?=urlencode($logged_user_id) ?>&class=<?=urlencode($class_id) ?>">⟵ Torna indietro</a></h1>
            <br><br>

            <h1>Valutazioni della classe</h1>
            <p>Cliccare sul voto per l'eliminazione</p>
            <?php foreach($materie as $m): ?>
                <h2 style="margin-top:40px;"><?= htmlspecialchars($m) ?></h2>

                <!-- ALL GRADES -->
                <div class="user-details-card">
                    <table>
                        <thead>
                            <tr>
                                <th>Studente</th>
                                <?php foreach($tipi as $t): ?>
                                    <th><?= htmlspecialchars($t) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($studenti as $studId => $studName): ?>
                                <tr>
                                    <td><?= htmlspecialchars($studName) ?></td>
                                    <?php foreach($tipi as $t): ?>
                                        <td>
                                            <?php foreach($matrix[$studId][$m][$t] as $v): ?>
                                                <?php if($v === '-'): ?>
                                                    <a href="grade.php?student=<?= urlencode($studId) ?>&class=<?= urlencode($class_id) ?>&date=<?= urlencode($date) ?>&materia=<?= urlencode($m) ?>&tipo=<?= urlencode($t) ?>&fascia_oraria=<?= urlencode($fascia_oraria) ?>">-</a>
                                                <?php else: ?>
                                                    <?php
                                                    $colorClass = '';
                                                    if(is_numeric($v['voto'])) $colorClass = ($v['voto'] >= 6) ? 'good-grade' : 'failing-grade';
                                                    ?>
                                                    <a href="delete-grade.php?user=<?= urlencode($logged_user_id) ?>&class=<?= urlencode($class_id) ?>&date=<?= urlencode($date) ?>&fascia_oraria=<?= urlencode($fascia_oraria) ?>&grade_id=<?= urlencode($v['id']) ?>" class="<?= $colorClass ?>"><?= htmlspecialchars($v['voto']) ?></a>
                                                <?php endif; ?>
                                                <?php if($v !== end($matrix[$studId][$m][$t])) echo ' '; ?>
                                            <?php endforeach; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
            <br><br>
        </main>

        <div class="right">
            <div class="announcements">
                <h2>Circolari recenti</h2>
                <div class="updates">
                    <div class="message">
                        <p><b>Scolastico</b><br>Tirocinio formativo estivo presso l'azienda micra.</p>
                        <small class="text-muted">2 minuti fa</small>
                    </div>
                    <div class="message">
                        <p><b>Attività extracurriculari</b><br>Opportunità di tirocinio globale da parte di un'organizzazione studentesca.</p>
                        <small class="text-muted">10 minuti fa</small>
                    </div>
                    <div class="message">
                        <p><b>Esami</b><br>Istruzioni per i test del fine trimestre.</p>
                        <small class="text-muted">Ieri</small>
                    </div>
                </div>
            </div>

            <div class="leaves">
                <h2>Insegnanti in congedo</h2>
                <div class="teacher">
                    <div class="profile-photo"><img src="../../../../private/images/user-icon.png" alt="pf"></div>
                    <div class="info">
                        <h3>Edoardo Moretti</h3>
                        <small class="text-muted">Intera giornata</small>
                    </div>
                </div>
                <div class="teacher">
                    <div class="profile-photo"><img src="../../../../private/images/user-icon.png" alt="pf"></div>
                    <div class="info">
                        <h3>Fioroni Alessio</h3>
                        <small class="text-muted">Mezza giornata</small>
                    </div>
                </div>
                <div class="teacher">
                    <div class="profile-photo"><img src="../../../../private/images/user-icon.png" alt="pf"></div>
                    <div class="info">
                        <h3>Damiano Perri</h3>
                        <small class="text-muted">Intera giornata</small>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="../../../../private/js/app.js"></script>
</body>
</html>
