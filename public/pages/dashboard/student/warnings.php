<?php
require_once "../../../../private/php/autoload.php";
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../../../../index.php");
    exit;
}

if(!isset($_GET['date']) || !isset($_GET['fascia_oraria']) || !isset($_GET['user'])){
    header("Location: student.php?user=" . urlencode($_SESSION['user_id']) . "&error=not-found");
    exit;
}

$user_id       = $_SESSION['user_id'];
$date_input    = $_GET['date'] ?? null;
$fascia_oraria = $_GET['fascia_oraria'] ?? null;
$conn          = getConnection();

$sql = "SELECT id, data, descrizione 
        FROM ammonizioni 
        WHERE user_id = ? 
        ORDER BY data DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notes = [];
while($row = $result->fetch_assoc()){
    $notes[$row['data']][] = [
        'id'          => $row['id'],
        'descrizione' => $row['descrizione']
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Didattix | Ammonizioni</title>
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
                <a href="student.php?date=<?=urlencode($date_input)?>&fascia_oraria=<?=urlencode($fascia_oraria) ?>&user=<?=urlencode($user_id)?>">
                    ⟵ Torna indietro
                </a>
            </h1>
            <br><br>

            <h1>Tutte le ammonizioni</h1>
            <div class="user-details-card">
                <table>
                    <tr>
                        <th>Data</th>
                        <th>Ammonizioni</th>
                    </tr>

                    <?php if(!empty($notes)): ?>
                        <?php foreach($notes as $date => $rows): ?>
                            <tr>
                                <td><?= htmlspecialchars(DateTime::createFromFormat('Y-m-d', $date)->format('d-m-Y')) ?></td>
                                <td>
                                    <?php foreach($rows as $nota): ?>
                                        <?php $desc = decrypt($nota['descrizione']); ?>
                                        <span class="material-icons-sharp note-tooltip" 
                                            data-desc="<?= htmlspecialchars($desc, ENT_QUOTES) ?>">
                                            description
                                        </span>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">Nessuna ammonizione trovata.</td>
                        </tr>
                    <?php endif; ?>
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