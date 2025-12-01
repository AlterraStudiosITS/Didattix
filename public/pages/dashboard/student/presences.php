<?php
    require_once "../../../../private/php/autoload.php";
    session_start();

    if(!isset($_SESSION['user_id'])){
        header("Location: ../../../../index.php");
        exit;
    }

    $logged_user_id = $_SESSION['user_id'];

    $conn = getConnection();

    $stmt = $conn->prepare("SELECT classe_id FROM studenti_classi WHERE user_id = ?");
    $stmt->bind_param("i", $logged_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()) $classe_id = $row['classe_id'];
    else die("Studente non assegnato a nessuna classe.");

    $stmt->close();

    $stmt = $conn->prepare("SELECT c.nome_classe, c.sezione, c.anno_scolastico, u.nome AS coordinatore_nome, u.cognome AS coordinatore_cognome
                            FROM classi c
                            LEFT JOIN users u ON c.coordinatore_id = u.id
                            WHERE c.id = ?");
    $stmt->bind_param("i", $classe_id);
    $stmt->execute();
    $classe = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $sql = "SELECT p.data, p.fascia_oraria, p.stato, p.signature_description,
                s.nome AS studente_nome, s.cognome AS studente_cognome,
                d.nome AS docente_nome, d.cognome AS docente_cognome
            FROM presenze p
            INNER JOIN users s ON p.user_id = s.id
            INNER JOIN users d ON p.teacher_id_signature = d.id
            WHERE p.classe_id = ? AND user_id = ?
            ORDER BY p.data, p.fascia_oraria, s.cognome";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $classe_id, $logged_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $presenze = [];
    while($row = $result->fetch_assoc()) $presenze[] = $row;

    $coordinatore_nome = decrypt($classe['coordinatore_nome']);
    $coordinatore_cognome = decrypt($classe['coordinatore_cognome']);
    $nome_classe = decrypt($classe['nome_classe']);
    $sezione = decrypt($classe['sezione']);
    $anno_scolastico = decrypt($classe['anno_scolastico']);

    $stmt->close();
    $conn->close();
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
            <h1>Registro presenze</h1><br><br>
            <h2>Dati classe</h2>
            <div class="user-details-card">
                <table>
                    <tr>
                        <th>Classe</th>
                        <td><?= htmlspecialchars($nome_classe . ' - ' . $sezione) ?></td>
                    </tr>
                    <tr>
                        <th>Anno scolastico</th>
                        <td><?= htmlspecialchars($anno_scolastico) ?></td>
                    </tr>
                    <tr>
                        <th>Coordinatore</th>
                        <td><?= htmlspecialchars($coordinatore_nome . ' ' . $coordinatore_cognome) ?></td>
                    </tr>
                </table>
            </div>

            <br><br>
            <h2>Presenze</h2>
            <div class="user-details-card">
                <table>
                    <tr>
                        <th>Data</th>
                        <th>Fascia oraria</th>
                        <th>Stato</th>
                        <th>Firma docente</th>
                    </tr>
                    <?php foreach($presenze as $presenza): ?>
                    <tr>
                        <td><?= htmlspecialchars($presenza['data']) ?></td>
                        <td><?= htmlspecialchars($presenza['fascia_oraria']) ?></td>
                        <td>
                            <?php 
                                $stato = $presenza['stato'];
                                if($stato === 'P') echo '<span class="stato presente">P</span>';
                                else if($stato === 'A') echo '<span class="stato assente">A</span>';
                                else echo htmlspecialchars($stato);
                            ?>
                        </td>
                        <td><?= htmlspecialchars(decrypt($presenza['docente_nome']) . ' ' . decrypt($presenza['docente_cognome'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
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