<?php
    require_once "../../../../private/php/autoload.php";
    session_start();

    if(!isset($_SESSION['user_id'])){
        header("Location: ../../../../index.php");
        exit;
    }

    $logged_user_id = $_SESSION['user_id'];

    if(!isset($_GET['user-edit']) || empty($_GET['user-edit'])){
        header('Location: admin.php?user=' . urlencode($logged_user_id));
        exit;
    }

    $id = $_GET['user-edit'];
    $conn = getConnection();

    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 0){
        echo "Utente non trovato.";
        exit;
    }

    $row = $result->fetch_assoc();
    $conn->close();

    $nome           = isset($row['nome']) ? decrypt($row['nome']) : '';
    $cognome        = isset($row['cognome']) ? decrypt($row['cognome']) : 'Non indicato';
    $user_type      = isset($row['user_type']) && $row['user_type'] !== null ? $row['user_type'] : 'Non trovato';
    $email          = isset($row['linked_email']) && $row['linked_email'] !== null ? decrypt($row['linked_email']) : 'Non associata';
    $codice_fiscale = isset($row['codice_fiscale']) && $row['codice_fiscale'] !== null ? decrypt($row['codice_fiscale']) : 'Non indicato';
    $data_nascita   = isset($row['data_di_nascita']) && $row['data_di_nascita'] !== null ? decrypt($row['data_di_nascita']) : 'Non indicato';
    $date           = isset($row['date']) && $row['date'] !== null ? decrypt($row['date']) : '';
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
            <a href="admin.php?user=<?= urlencode($logged_user_id) ?>" class="active">
                <span class="material-icons-sharp">home</span>
                <h3>Home</h3>
            </a>
            <a href="admin.php?user=<?= urlencode($logged_user_id) ?>" onclick="timeTableAll()">
                <span class="material-icons-sharp">today</span>
                <h3>Orario</h3>
            </a> 
            <a href="admin.php?user=<?= urlencode($logged_user_id) ?>">
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
            <h1>Modifica dell'utente</h1><br><br>
            <h2>Dati</h2>
            <div class="user-details-card">
                <table>
                    <tr>
                        <th>Nome</th>
                        <td><?= htmlspecialchars($nome) ?></td>
                    </tr>
                    <tr>
                        <th>Cognome</th>
                        <td><?= htmlspecialchars($cognome) ?></td>
                    </tr>
                    <tr>
                        <th>Tipo di utente</th>
                        <td><?= htmlspecialchars($user_type) ?></td>
                    </tr>
                    <tr>
                        <th>Email associata</th>
                        <td><?= htmlspecialchars($email) ?></td>
                    </tr>
                    <tr>
                        <th>Codice fiscale</th>
                        <td><?= htmlspecialchars($codice_fiscale) ?></td>
                    </tr>
                    <tr>
                        <th>Data di nascita</th>
                        <td><?= htmlspecialchars($data_nascita) ?></td>
                    </tr>
                    <tr>
                        <th>Creazione account</th>
                        <td><?= htmlspecialchars($date) ?></td>
                    </tr>
                </table>
            </div>

            <br><br>
            <h2>Azioni</h2>
            <div class="buttons">
                <a href="edit-user-password.php?user=<?= urlencode($id) ?>" class="btn-discord btn-blue">Modifica password</a>
                <button class="btn-discord btn-red" onclick="showPopup()">Elimina account</button>
            </div>
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
    <div id="deletePopup" class="popup-overlay">
        <div class="popup-box">
            <h2>Sei sicuro?</h2>
            <p>Questa azione è irreversibile.<br>Vuoi davvero eliminare l'account?</p>
            <div class="popup-buttons">
                <button onclick="hidePopup()" class="btn-discord btn-blue">Annulla</button>
                <a href="../../../../private/php/sessions/delete-user.php?user=<?= urlencode($logged_user_id) ?>&user-edit=<?= urlencode($id) ?>" class="btn-discord btn-red">Sì, elimina</a>
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

        function showPopup(){
            document.getElementById("deletePopup").style.display = "flex";
        }

        function hidePopup(){
            document.getElementById("deletePopup").style.display = "none";
        }
    </script>
</body>
</html>