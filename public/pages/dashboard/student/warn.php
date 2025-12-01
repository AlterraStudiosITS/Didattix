<?php
require_once "../../../../private/php/autoload.php";
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../../../../index.php");
    exit;
}

if(!isset($_GET['id']) || !isset($_GET['date']) || !isset($_GET['fascia_oraria']) || !isset($_GET['user'])){
    header("Location: student.php?user=" . urlencode($_SESSION['user_id']) . "&error=not-found");
    exit;
}

$logged_user_id = $_SESSION['user_id'];
$note_id        = intval($_GET['id']);
$date           = $_GET['date'];
$fascia_oraria  = $_GET['fascia_oraria'];

$conn = getConnection();

$sql = "SELECT a.id, a.user_id, a.classe_id, a.teacher_id, a.descrizione, a.data,
               u.nome AS studente_nome, u.cognome AS studente_cognome,
               t.nome AS docente_nome, t.cognome AS docente_cognome
        FROM ammonizioni a
        INNER JOIN users u ON a.user_id = u.id
        INNER JOIN users t ON a.teacher_id = t.id
        WHERE a.id = ? AND a.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $note_id, $logged_user_id);
$stmt->execute();
$result = $stmt->get_result();

$nota = $result->fetch_assoc();
$stmt->close();
$conn->close();

if(!$nota){
    header("Location: student.php?date=" . urlencode($date) . "&fascia_oraria=" . urlencode($fascia_oraria) . "&user=" . urlencode($logged_user_id) . "&error=note-not-found");
    exit;
}

$studentId     = $nota['user_id'];
$studentName   = decrypt($nota['studente_cognome']) . ' ' . decrypt($nota['studente_nome']);
$formattedDate = $nota['data'];
$desc          = decrypt($nota['descrizione']);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Didattix | Home</title>
    <link rel="shortcut icon" href="../../../../private/images/logo.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="../../../../private/css/style.css">
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
            <a href="student.php?user=<?= urlencode($logged_user_id) ?>" onclick="timeTableAll()">
                <span class="material-icons-sharp">today</span>
                <h3>Orario</h3>
            </a> 
            <a href="student.php?user=<?= urlencode($logged_user_id) ?>">
                <span class="material-icons-sharp">grid_view</span>
                <h3>Esami</h3>
            </a>
            <a href="../password.php?user=<?= urlencode($logged_user_id) ?>">
                <span class="material-icons-sharp">password</span>
                <h3>Resetta password</h3>
            </a>
            <a href="../../../../private/php/sessions/logout.php">
                <span class="material-icons-sharp" onclick="">logout</span>
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
            <h1>
                <a href="student.php?date=<?= urlencode($date)?>&fascia_oraria=<?= urlencode($fascia_oraria) ?>&user=<?=urlencode($logged_user_id)?>">
                    ⟵ Torna indietro
                </a>
            </h1>
            <br><br>

            <h1>Ammonizione studente</h1>
            <div class="user-details-card">
                <table>
                    <tr>
                        <th>Data</th>
                        <th>Studente</th>
                        <th>Descrizione</th>
                    </tr>
                    <tr>
                        <td><?= htmlspecialchars($formattedDate) ?></td>
                        <td><?= htmlspecialchars($studentName) ?></td>
                        <td>
                            <span class="material-icons-sharp note-tooltip" 
                                data-desc="<?= htmlspecialchars($desc, ENT_QUOTES) ?>">
                                description
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
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
    <script>
        const searchInput = document.getElementById('searchUser');
        const userList = document.getElementById('userList');

        searchInput.addEventListener('input', () => {
            const filter = searchInput.value.toLowerCase();
            const utentiDiv = userList.querySelectorAll('div');

            utentiDiv.forEach(div => {
                const nome = div.querySelector('h3').textContent.toLowerCase();
                if(nome.includes(filter)) div.style.display = '';
                else div.style.display = 'none';
            });
        });

        const searchClass = document.getElementById('searchClass');
        const classList = document.getElementById('classList');

        searchClass.addEventListener('input', () => {
            const filter = searchClass.value.toLowerCase();
            const classDivs = classList.querySelectorAll('div');

            classDivs.forEach(div => {
                const nome = div.querySelector('h3').textContent.toLowerCase();
                div.style.display = nome.includes(filter) ? '' : 'none';
            });
        });
    </script>
</body>
</html>