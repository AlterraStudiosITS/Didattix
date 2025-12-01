<?php
require_once "../../../../private/php/autoload.php";
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../../../../index.php");
    exit;
}

$logged_user_id = $_SESSION['user_id'];

if(!isset($_GET['date'])){
    header("Location: student.php?error=missing-date&user=" . urlencode($logged_user_id));
    exit;
}

$date          = $_GET['date'];
$fascia_oraria = $_GET['fascia_oraria'];

$conn = getConnection();

/* ───────────────────────────────
   Student class
   ─────────────────────────────── */
$stmt = $conn->prepare("
    SELECT classe_id 
    FROM studenti_classi 
    WHERE user_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $logged_user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if(!$row){
    header("Location: student.php?error=no-class-found&user=" . urlencode($logged_user_id));
    exit;
}

$class_id = (int)$row['classe_id'];

/* ───────────────────────────────
   Class info
   ─────────────────────────────── */
$stmt = $conn->prepare("
    SELECT nome_classe, sezione, anno_scolastico 
    FROM classi 
    WHERE id = ?
");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$res = $stmt->get_result();
$class = $res->fetch_assoc();
$stmt->close();

if(!$class){
    header("Location: student.php?error=class-not-found&user=" . urlencode($logged_user_id));
    exit;
}

$class_nome    = htmlspecialchars($class['nome_classe']);
$class_sezione = htmlspecialchars($class['sezione']);
$class_anno    = htmlspecialchars($class['anno_scolastico']);

/* ───────────────────────────────
   Student subjects
   ─────────────────────────────── */
$stmt = $conn->prepare("
    SELECT DISTINCT materia 
    FROM valutazioni 
    WHERE user_id = ? AND classe_id = ?
    ORDER BY materia ASC
");
$stmt->bind_param("ii", $logged_user_id, $class_id);
$stmt->execute();
$res = $stmt->get_result();

$materie = [];
while($row = $res->fetch_assoc()){
    $materie[] = decrypt($row['materia']);
}
$stmt->close();

$tipi = ['Orale', 'Scritto', 'Pratico', 'Altro'];

/* ───────────────────────────────
   Student votes
   ─────────────────────────────── */
$stmt = $conn->prepare("
    SELECT id, materia, voto, tipo 
    FROM valutazioni
    WHERE user_id = ? AND classe_id = ?
    ORDER BY data DESC
");
$stmt->bind_param("ii", $logged_user_id, $class_id);
$stmt->execute();
$res = $stmt->get_result();

$valutazioni = [];

while($row = $res->fetch_assoc()){
    $materia = decrypt($row['materia']);
    $voto    = rtrim(rtrim($row['voto'], '0'), '.');
    $tipo    = $row['tipo'];

    $valutazioni[$materia][$tipo][] = [
        'id'   => $row['id'],
        'voto' => $voto
    ];
}
$stmt->close();
$conn->close();

function calcolaMediaStudente($valutazioni, $materia){
    if(!isset($valutazioni[$materia])) return "-";

    $somma = 0;
    $count = 0;

    foreach(['Orale','Scritto','Pratico','Altro'] as $t){
        if(isset($valutazioni[$materia][$t])){
            foreach($valutazioni[$materia][$t] as $v){
                if(is_numeric($v['voto'])){
                    $somma += (float)$v['voto'];
                    $count++;
                }
            }
        }
    }

    return ($count > 0) ? round($somma / $count, 2) : "-";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DiDattix | Voti</title>
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
        min-width: 32px;
        text-align: center;
    }
    .good-grade{ background:#155724; color:#d4edda; }
    .failing-grade{ background:#721c24; color:#f8d7da; }
    </style>
</head>
<body>

<header>
    <div class="logo">
        <img src="../../../../private/images/logo.png">
        <h2>Di<span class="danger">Dattix</span></h2>
    </div>

    <div class="navbar">
        <a href="student.php?user=<?=urlencode($logged_user_id) ?>" class="active">
            <span class="material-icons-sharp">home</span>
            <h3>Home</h3>
        </a>
        <a href="../password.php?user=<?=urlencode($logged_user_id) ?>">
            <span class="material-icons-sharp">password</span>
            <h3>Resetta password</h3>
        </a>
        <a href="../../../../private/php/sessions/logout.php">
            <span class="material-icons-sharp">logout</span>
            <h3>Esci</h3>
        </a>
    </div>

    <div id="profile-btn" style="display:none;">
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
                    <img src="../../../../private/images/user-icon.png">
                </div>
                <div class="info">
                    <p>Ciao, <b><?= htmlspecialchars($_SESSION['username'] ?? 'Utente') ?></b></p>
                </div>
            </div>
        </div>
    </aside>

    <main>

        <h1>
            <a href="student.php?user=<?=urlencode($logged_user_id) ?>&class=<?=urlencode($class_id) ?>&date=<?=urlencode($date) ?>&fascia_oraria=<?=urldecode($fascia_oraria)?>">⟵ Torna indietro</a>
        </h1>

        <br><br>
        <h1>I tuoi voti</h1>

        <?php if(empty($materie)): ?>
            <p>Nessun voto presente.</p>
        <?php endif; ?>

        <?php foreach($materie as $m): ?>
            <h2 style="margin-top:40px;"><?= htmlspecialchars($m) ?> 
                <small style="font-size:16px; opacity:.7;">(Media: <?= calcolaMediaStudente($valutazioni, $m) ?>)</small>
            </h2>

            <div class="user-details-card">
                <table>
                    <thead>
                        <tr>
                            <?php foreach($tipi as $t): ?>
                                <th><?= htmlspecialchars($t) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <?php foreach($tipi as $t): ?>
                                <td>
                                    <?php
                                    $voti = $valutazioni[$m][$t] ?? [];

                                    if(empty($voti)){
                                        echo '<span>-</span>';
                                    } else {
                                        foreach($voti as $v){
                                            $color = '';
                                            if(is_numeric($v['voto'])){
                                                $color = ($v['voto'] >= 6) ? 'good-grade' : 'failing-grade';
                                            }
                                            echo '<span class="'.$color.'">'.htmlspecialchars($v['voto']).'</span> ';
                                        }
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
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
            </div>
        </div>
    </div>

</div>

<script src="../../../../private/js/app.js"></script>

</body>
</html>
