<?php
    require_once "../../../../private/php/autoload.php";
    session_start();

    if(!isset($_SESSION['user_id'])){
        header("Location: ../../../../index.php");
        exit;
    }

    $username      = $_SESSION['username'] ?? null;
    $user_id       = $_SESSION['user_id'] ?? null;
    $fascia_oraria = $_GET['fascia_oraria'] ?? null;
    $date_input    = $_GET['date'] ?? null;

    $conn          = getConnection();

    $date_sql = DateTime::createFromFormat('d-m-Y', $date_input);
    if($date_sql) $date_sql = $date_sql->format('Y-m-d');
    else $date_sql = date('Y-m-d');

    // ---- PRESENZE ----
    $sql  = "SELECT stato FROM presenze WHERE user_id = ? AND data = ? AND fascia_oraria = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $date_sql, $fascia_oraria);
    $stmt->execute();
    $stmt->bind_result($stato);
    $stmt->fetch();
    $stmt->close();

    if($stato === false){
        $letter = '-';
        $statusClass = 'unknown';
    }elseif($stato == 'P'){
        $letter = 'P';
        $statusClass = 'presente';
    }elseif($stato == 'A'){
        $letter = 'A';
        $statusClass = 'assente';
    }else{
        $letter = '-';
        $statusClass = 'unknown';
    }

    $box = '
    <div class="' . $statusClass . '">
        <h2>' . $letter . '</h2>
        <br>
        <a href="./presences.php?user=' . $user_id . '">Visualizza le assenze ⟶</a>
    </div>
    ';

    // ---- AMMONIZIONI ----
    $sql = "SELECT id FROM ammonizioni WHERE user_id = ? AND data = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $date_sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $boxNota = '';
    while($row = $result->fetch_assoc()){
        $note_id = $row['id'];
        $boxNota .= '
        <div class="nota-box">
            <h2><span class="material-icons-sharp">report</span></h2>
            <p>Ammonizione</p>
            <br>
            <a href="warn.php?date=' . $date_input . '&fascia_oraria=' . $fascia_oraria . '&user=' . $user_id . '&id=' . $note_id . '">Leggi ⟶</a>
        </div>
        ';
    }
    $stmt->close();

    // ---- AMMONIZIONI RECENTI (max 5) ----
    $sql = "SELECT id, data, descrizione 
            FROM ammonizioni 
            WHERE user_id = ? 
            ORDER BY data DESC 
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $recentNotes = '';
    while($row = $result->fetch_assoc()){
        $note_id = $row['id'];
        $desc    = substr(decrypt($row['descrizione']), 0, 30) . '...';
        $date    = DateTime::createFromFormat('Y-m-d', $row['data'])->format('d-m-Y');

        $recentNotes .= '
        <div class="note-box">
            <span class="material-icons-sharp">report</span>
            <div>
                <h3>' . htmlspecialchars($date) . '</h3>
                <p>' . htmlspecialchars($desc) . '</p>
                <a href="warn.php?date=' . $date . '&fascia_oraria=' . $fascia_oraria . '&user=' . $user_id . '&id=' . $note_id . '">Leggi ⟶</a>
            </div>
        </div>
        ';
    }
    $stmt->close();

    if($recentNotes === '') $recentNotes = '<p>Nessuna ammonizione recente.</p>';

    // --- FIRMA SELEZIONATA ---
    $sql = "SELECT p.signature_description, u.nome AS docente_nome, u.cognome AS docente_cognome
        FROM presenze p
        INNER JOIN users u ON u.id = p.teacher_id_signature
        WHERE p.user_id = ? AND p.data = ? AND p.fascia_oraria = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $date_sql, $fascia_oraria);
    $stmt->execute();
    $result = $stmt->get_result();

    $firmaRows = [];
    while($row = $result->fetch_assoc()) $firmaRows[] = $row;

    $stmt->close(); 

    // ---- VOTI GIORNALIERI ----
    $sql = "SELECT materia, voto, tipo 
            FROM valutazioni 
            WHERE user_id = ? 
            AND data = ? 
            ORDER BY id DESC 
            LIMIT 4";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $date_sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $boxVoti = '';
    $foundGrades = false;

    while($row = $result->fetch_assoc()){
        $foundGrades = true;

        $voto = floatval($row['voto']);
        $materia = decrypt($row['materia']);
        $tipo = htmlspecialchars($row['tipo']);

        if($voto >= 6) $colorClass = 'good-grade';
        else $colorClass = 'failing-grade';

        $boxVoti .= '
        <div class="grade-box ' . $colorClass . '">
            <h2>' . $voto . '</h2>
            <p>' . $materia . ' (' . $tipo . ')</p>
            <a href="./grades.php?user=' . $user_id . '&date=' . $date_input . '&fascia_oraria=' . $fascia_oraria . '">Visualizza voti ⟶</a>
        </div>
        ';
    }
    $stmt->close();

    if(!$foundGrades){
        $boxVoti = '
        <div class="grade-box unknown">
            <h2>-</h2>
            <p>Nessun voto</p>
            <br>
            <a href="./grades.php?user=' . $user_id . '&date=' . $date_input . '&fascia_oraria=' . $fascia_oraria . '">Visualizza voti ⟶</a>
        </div>
        ';
    }

    // ---- RECENT GRADES (max 5) ----
    $sql = "SELECT materia, voto, tipo, data 
            FROM valutazioni 
            WHERE user_id = ? 
            ORDER BY data DESC 
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $recentGrades = '';
    while($row = $result->fetch_assoc()){
        $materia = htmlspecialchars(decrypt($row['materia']));
        $voto = floatval($row['voto']);
        $tipo = htmlspecialchars($row['tipo']);
        $date = DateTime::createFromFormat('Y-m-d', $row['data'])->format('d-m-Y');

        $gradeClass = $voto >= 6 ? 'good-grade' : 'failing-grade';

        $recentGrades .= '
        <div class="note-box">
            <span class="material-icons-sharp">assessment</span>
            <div>
                <h3>' . htmlspecialchars($materia) . '</h3>
                <p>Voto: ' . $voto . ' (' . $tipo . ')</p>
                <p>Data: ' . htmlspecialchars($date) . '</p>
                <div class="' . $gradeClass . '">
                    ' . ($voto >= 6 ? 'Buono' : 'Insufficiente') . '
                </div>
                <br>
                <a href="./grades.php?user=' . $user_id . '&date=' . $date_input . '&fascia_oraria=' . $fascia_oraria . '">Vedi tutti ⟶</a>
            </div>
        </div>
        ';
    }
    $stmt->close();

    if($recentGrades === '') $recentGrades = '<p>Nessun voto recente.</p>';

    $conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Didattix | Dashboard</title>
    <link rel="shortcut icon" href="../../../../private/image/logo.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="../../../../private/css/style.css">

    <!-- Flatpickr API -->
    <link id="flatpickr-theme" rel="stylesheet" href="">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/it.js"></script>

    <script src="../../../../private/config/config.js"></script>
    <script src="../../../../private/js/student-app.js"></script>

    <style>
        .grade-box .good-grade{
            background-color: #155724;
            color: #d4edda;
        }

        .grade-box .failing-grade{
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
            <a href="student.php?user=<?= urlencode($user_id) ?>" class="active">
                <span class="material-icons-sharp">home</span>
                <h3>Home</h3>
            </a>
            <a href="student.php?user=<?= urlencode($user_id) ?>" onclick="timeTableAll()">
                <span class="material-icons-sharp">today</span>
                <h3>Orario</h3>
            </a> 
            <a href="student.php?user=<?= urlencode($user_id) ?>">
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
            <h1>Informazioni</h1>
            <div class="info">
                <div class="search-user-bar">
                    <form method="GET" class="search-user-div">
                        <span class="material-icons-sharp">calendar_today</span>
                        <input
                            type="text"
                            id="selected_date"
                            name="date"
                            class="search-user"
                            value="<?= isset($_GET['date']) ? htmlspecialchars(date('d-m-Y', strtotime($_GET['date']))) : date('d-m-Y') ?>"
                        >
                        <select name="fascia_oraria" id="fascia_oraria" class="search-user">
                            <?php
                                $fasce = [
                                    "08:00 - 09:00",
                                    "09:00 - 10:00",
                                    "10:00 - 11:00",
                                    "11:00 - 12:00",
                                    "12:00 - 13:00",
                                    "13:00 - 14:00"
                                ];
                                $selectedFascia = $_GET['fascia_oraria'] ?? '';
                                foreach($fasce as $fascia){
                                    $selected = $fascia === $selectedFascia ? 'selected' : '';
                                    echo "<option value=\"$fascia\" $selected>$fascia</option>";
                                }
                            ?>
                        </select>
                    </form>
                </div>

                <div class="subjects">
                    <?= $box ?>
                    <?= $boxNota ?>
                    <?= $boxVoti ?>
                </div>
            </div>

            <br><br>
            <h1>Voti recenti <span>(<a href="./grades.php?user=' . $user_id . '&date=' . $date_input . '&fascia_oraria=' . $fascia_oraria . '">Vedi tutti ⟶</a>)</span></h1>
            <div class="subjects"><?= $recentGrades ?></div>

            <br><br>
            <h1>Ammonizioni recenti <span>(<a href="warnings.php?date=<?= urlencode($date_input) ?>&fascia_oraria=<?= urlencode($fascia_oraria) ?>&user=<?= urlencode($user_id) ?>">Vedi tutte ⟶</a>)</span></h1>
            <div class="subjects">
                <?= $recentNotes ?>
            </div>
            
            <br><br>
            <h1>Compiti</h1>
            <p>Non ci sono compiti in agenda.</p>

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

    <script>
        document.querySelectorAll('#selected_date, #fascia_oraria').forEach(el => {
            el.addEventListener('change', () => {
                const date    = document.getElementById('selected_date').value;
                const fascia  = document.getElementById('fascia_oraria').value;
                const user    = "<?= urlencode($user_id) ?>";

                const url = new URL(window.location.href.split('?')[0]);
                url.searchParams.set('user', user);
                url.searchParams.set('date', date);
                url.searchParams.set('fascia_oraria', fascia);

                window.location.href = url.toString();
            });
        });
    </script>
</body>
</html>