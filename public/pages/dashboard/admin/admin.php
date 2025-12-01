<?php
    session_start();
    require_once "../../../../private/php/autoload.php";

    if(!isset($_SESSION['user_id'])){
        header("Location: ../../../../index.php");
        exit;
    }

    $username = $_SESSION['username'] ?? null;
    $user_id = $_SESSION['user_id'] ?? null;

    $possibleClasses = ['eg', 'mth', 'cs', 'cg', 'net'];

    $conn = getConnection();

    // === CLASSES ===
    $sqlClasses = "SELECT id, nome_classe, sezione FROM classi";
    $resultClasses = $conn->query($sqlClasses);

    $classes = [];
    if($resultClasses && $resultClasses->num_rows > 0){
        while($row = $resultClasses->fetch_assoc()){
            $id           = $row['id'];
            $class_name   = strtoupper(decrypt($row['nome_classe']));
            $sezione      = decrypt($row['sezione']);
            $classeRandom = $possibleClasses[array_rand($possibleClasses)];

            $classes[] = [
                'id'      => $id,
                'nome'    => $class_name,
                'sezione' => $sezione,
                'classe'  => $classeRandom
            ];
        }

        usort($classes, function($a, $b){
            $a_letter = substr($a['nome'], -1);
            $b_letter = substr($b['nome'], -1);

            $a_number = intval(substr($a['nome'], 0, -1));
            $b_number = intval(substr($b['nome'], 0, -1));

            $cmp = strcmp($a_letter, $b_letter);
            if($cmp !== 0) return $cmp;

            return $a_number - $b_number;
        });
    }else $classes = false;

    // === USERS ===
    $sql = "SELECT id, nome, cognome FROM users ORDER BY nome ASC";
    $result = $conn->query($sql);

    $utenti = [];
    if($result && $result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $id           = $row['id'];
            $nome         = decrypt($row['nome']);
            $cognome      = $row['cognome'] ? decrypt($row['cognome']) : '';
            $nomeCompleto = htmlspecialchars(trim("$nome $cognome"));
            $classeRandom = $possibleClasses[array_rand($possibleClasses)];
            
            $utenti[] = ['id' => $id, 'nome' => $nomeCompleto, 'classe' => $classeRandom];
        }
    }else $utenti = false;

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
            <h2>Di<span class="danger">DiDattix</span></h2>
        </div>
        <div class="navbar">
            <a href="admin.php?user=<?= urlencode($user_id)?>" class="active">
                <span class="material-icons-sharp">home</span>
                <h3>Home</h3>
            </a>
            <a href="admin.php?user=<?= urlencode($user_id)?>" onclick="timeTableAll()">
                <span class="material-icons-sharp">today</span>
                <h3>Orario</h3>
            </a> 
            <a href="admin.php?user=<?= urlencode($user_id)?>">
                <span class="material-icons-sharp">grid_view</span>
                <h3>Esami</h3>
            </a>
            <a href="../password.php?user=<?= urlencode($user_id) ?>">
                <span class="material-icons-sharp">password</span>
                <h3>Cambia password</h3>
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
                        <p>Ciao, <b><?= htmlspecialchars($username) ?></b></p>
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
            <h1>Opzioni amministratore</h1>
            <div class="subjects">
                <div class="cg">
                    <a href="create_user.php?user=<?= urlencode($user_id)?>">
                        <h3>Crea utente  [ + ]</h3>
                        <span class="material-icons-sharp">person</span>
                    </a>
                </div>
                <div class="eg">
                    <a href="./classes/create_class.php?user=<?= urlencode($user_id)?>">
                        <h3>Crea classe [ + ]</h3>
                        <span class="material-icons-sharp">book</span>
                    </a>
                </div>
            </div>

            <br><br>
            <div class="search-user-bar">
                <h1>Seleziona una classe</h1>
                <div class="search-user-div">
                    <span class="material-icons-sharp">search</span>
                    <input type="text" class="search-user" id="searchClass" placeholder="Cerca classe...">
                </div>
            </div>
            <div class="subjects" id="classList">
                <?php if($classes): ?>
                    <?php foreach($classes as $class): ?>
                        <div class="<?= $class['classe'] ?>">
                            <span class="material-icons-sharp">book</span>
                            <h3><?= $class['nome'] ?></h3>
                            <p style="font-size: 15px"><?= $class['sezione'] ?></p><br>
                            <a href="./classes/edit-class.php?user=<?= urlencode($user_id) ?>&class=<?= urlencode($class['id']) ?>" class="<?= $class['classe'] ?> user-card-link">Clicca qui per modificare ⟶</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nessuna classe trovata.</p>
                <?php endif; ?>
            </div>

            <br><br>
            <div class="search-user-bar">
                <h1>Seleziona un utente</h1>
                <div class="search-user-div">
                    <span class="material-icons-sharp">search</span>
                    <input type="text" class="search-user" id="searchUser" placeholder="Cerca utente...">
                </div>
            </div>
            <div class="subjects" id="userList">
                <?php if($utenti): ?>
                    <?php foreach($utenti as $utente): ?>
                        <div class="<?= $utente['classe'] ?>">
                            <span class="material-icons-sharp">person</span>
                            <h3><?= $utente['nome'] ?></h3>
                            <a href="edit-user.php?user=<?= urlencode($user_id) ?>&user-edit=<?= isset($utente['id']) && $utente['id'] !== null ? urlencode($utente['id']) : '' ?>" class="<?= $utente['classe'] ?> user-card-link">Clicca qui per modificare ⟶</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nessun utente trovato.</p>
                <?php endif; ?>
            </div>

            <div class="timetable" id="timetable">
                <div>
                    <span id="prevDay">&lt;</span>
                    <h2>Orario giornaliero</h2>
                    <span id="nextDay">&gt;</span>
                </div>
                <span class="closeBtn" onclick="timeTableAll()">X</span>
                <table>
                    <thead>
                        <tr>
                            <th>Ore</th>
                            <th>Aula</th>
                            <th>Materia</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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

    <script src="../../../../private/js/app.js"></script>
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