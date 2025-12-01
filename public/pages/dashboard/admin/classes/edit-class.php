<?php
    require_once "../../../../../private/php/autoload.php";
    session_start();

    if(!isset($_SESSION['user_id'])){
        header("Location: ../../../../../index.php");
        exit;
    }

    if(!isset($_GET['user']) || empty($_GET['user'])){
        header('Location: ../admin.php');
        exit;
    }

    $logged_user_id = $_SESSION['user_id'];
    $class_id       = $_GET['class'];
    $conn           = getConnection();

    $sql = "SELECT nome_classe, sezione, anno_scolastico, coordinatore_id FROM classi WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result && $result->num_rows > 0){
        $row = $result->fetch_assoc();
        
        $name            = decrypt($row['nome_classe']);
        $section         = decrypt($row['sezione']);
        $anno_scolastico = decrypt($row['anno_scolastico']);
        $coord_id        = null;
    }else{
        header("Location: ../admin.php?error=notfound");
        exit;
    }
    $stmt->close();
    $conn->close();

    function getTeachersClass($class_id){
        $conn = getConnection();

        $stmt = $conn->prepare("  
            SELECT u.id, u.nome, u.cognome, u.data_di_nascita, dc.materia, dc.ruolo
            FROM users u
            JOIN docenti_classi dc ON u.id = dc.user_id
            WHERE dc.classe_id = ?
        ");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $teachers = [];
        while($row = $result->fetch_assoc()) {
            $row['nome'] = decrypt($row['nome']);
            $row['cognome'] = decrypt($row['cognome']);
            $row['data_di_nascita'] = decrypt($row['data_di_nascita']);
            $row['materia'] = decrypt($row['materia']);
            $teachers[] = $row;
        }

        $stmt->close();
        $conn->close();

        usort($teachers, function($a, $b){
            $cmp = strcmp($a['cognome'], $b['cognome']);
            if($cmp === 0) return strcmp($a['nome'], $b['nome']);
            return $cmp;
        });

        return $teachers;
    }

    $students = getStudentsClass($class_id);
    $teachers = getTeachersClass($class_id);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Didattix | Home</title>
    <link rel="shortcut icon" href="../../../../../private/images/logo.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="../../../../../private/css/style.css">
</head>
<body>
    <header>
        <div class="logo" title="DiDattix">
            <img src="../../../../../private/images/logo.png" alt="logo">
            <h2>Di<span class="danger">Dattix</span></h2>
        </div>
        <div class="navbar">
            <a href="../admin.php?user=<?= urlencode($logged_user_id) ?>" class="active">
                <span class="material-icons-sharp">home</span>
                <h3>Home</h3>
            </a>
            <a href="../admin.php?user=<?= urlencode($logged_user_id) ?>" onclick="timeTableAll()">
                <span class="material-icons-sharp">today</span>
                <h3>Orario</h3>
            </a> 
            <a href="../admin.php?user=<?= urlencode($logged_user_id) ?>">
                <span class="material-icons-sharp">grid_view</span>
                <h3>Esami</h3>
            </a>
            <a href="../../password.php?user=<?= urlencode($logged_user_id) ?>">
                <span class="material-icons-sharp">password</span>
                <h3>Resetta password</h3>
            </a>
            <a href="../../../../../private/php/sessions/logout.php">
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
                        <img src="../../../../../private/images/user-icon.png" alt="user">
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
            <h1>Modifica della classe</h1><br><br>
            <h2>Dati</h2>
            <div class="user-details-card">
                <table>
                    <tr>
                        <th>ID</th>
                        <td>#<?= htmlspecialchars($class_id) ?></td>
                    </tr>
                    <tr>
                        <th>Classe</th>
                        <td><?= htmlspecialchars($name) ?></td>
                    </tr>
                    <tr>
                        <th>Indirizzo di studio</th>
                        <td><?= htmlspecialchars($section) ?></td>
                    </tr>
                    <tr>
                        <th>Studenti</th>
                        <td>Nessuno</td>
                    </tr>
                    <tr>
                        <th>Insegnanti</th>
                        <td>Nessuno</td>
                    </tr>
                    <tr>
                        <th>Coordinatore</th>
                        <td>Nessuno</td>
                    </tr>
                </table>
            </div>

            <br><br>
            <h2>Azioni</h2>
            <div class="buttons">
                <a href="add-student.php?user=<?= urlencode($logged_user_id) ?>&class=<?= urlencode($class_id) ?>" class="btn-discord btn-blue">Aggiungi studenti</a>
                <a href="add-teacher.php?user=<?= urlencode($logged_user_id) ?>&class=<?= urlencode($class_id) ?>" class="btn-discord btn-blue">Aggiungi docente</a>
                <button class="btn-discord btn-red" onclick="showDeletePopup()">Elimina classe</button>
            </div>

            <!-- ===== STUDENTI ===== -->
            <br><br>
            <h2>Studenti nella classe</h2>
            <div class="student-list">
                <?php if(empty($students)): ?>
                    <p>Non c'è nessuno studente assegnato a questa classe.</p>
                <?php else: ?>
                    <?php foreach($students as $index => $student): ?>
                        <div class="student-card">
                            <div class="student-details">
                                <div class="student-number"><?= $index + 1 ?></div>
                                <div class="student-info">
                                    <h3><?= htmlspecialchars($student['cognome'] . ' ' . $student['nome']) ?></h3>
                                    <p>Data di nascita: <?= htmlspecialchars($student['data_di_nascita']) ?></p>
                                </div>
                            </div>
                            <div class="student-status">
                                <div class="status present">P</div>
                                <div class="status absent">A</div>
                                <button class="status delete" data-student-id="<?= $student['id'] ?>" onclick="showPopup(this)">
                                    <span class="material-icons-sharp">delete</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- ===== DOCENTI ===== -->
            <br><br>
            <h2>Insegnanti nella classe</h2>
            <div class="student-list">
                <?php if (empty($teachers)): ?>
                    <p>Non c'è nessun insegnante incaricato in questa classe.</p>
                <?php else: ?>
                    <?php foreach($teachers as $index => $teacher): ?>
                        <div class="student-card">
                            <div class="student-details">
                                <div class="student-number"><?= $index + 1 ?></div>
                                <div class="student-info">
                                    <h3><?= htmlspecialchars($teacher['cognome'] . ' ' . $teacher['nome']) ?></h3>
                                    <p>Materia incaricata: <?= htmlspecialchars($teacher['materia']) ?></p>
                                    <p>Data di nascita: <?= htmlspecialchars($teacher['data_di_nascita']) ?></p>
                                </div>
                            </div>
                            <div class="student-status">
                                <button class="status delete" data-teacher-id="<?= $teacher['id'] ?>" onclick="showPopup(this)">
                                    <span class="material-icons-sharp">delete</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
                    <div class="profile-photo"><img src="../../../../../private/images/user-icon.png" alt="pf"></div>
                    <div class="info">
                        <h3>Edoardo Moretti</h3>
                        <small class="text-muted">Intera giornata</small>
                    </div>
                </div>
                <div class="teacher">
                    <div class="profile-photo"><img src="../../../../../private/images/user-icon.png" alt="pf"></div>
                    <div class="info">
                        <h3>Fioroni Alessio</h3>
                        <small class="text-muted">Mezza giornata</small>
                    </div>
                </div>
                <div class="teacher">
                    <div class="profile-photo"><img src="../../../../../private/images/user-icon.png" alt="pf"></div>
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
            <p>Questa azione è irreversibile.<br>Vuoi davvero rimuovere lo studente dalla classe?</p>
            <div class="popup-buttons">
                <button onclick="hidePopup()" class="btn-discord btn-blue">Annulla</button>
                <a href="../../../../../private/php/sessions/delete-student.php?student=" class="btn-discord btn-red">Sì, elimina</a>
            </div>
        </div>
    </div>
    <div id="deleteClassPopup" class="popup-overlay">
        <div class="popup-box">
            <h2>Sei sicuro?</h2>
            <p>Questa azione è irreversibile.<br>Vuoi davvero eliminare la classe?</p>
            <div class="popup-buttons">
                <button onclick="hideDeletePopup()" class="btn-discord btn-blue">Annulla</button>
                <a href="../../../../../private/php/sessions/delete-class.php?user=<?= urlencode($logged_user_id) ?>&class=<?= urlencode($class_id) ?>" class="btn-discord btn-red">Sì, elimina</a>
            </div>
        </div>
    </div>

    <script src="../../../../../private/js/app.js"></script>
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

        function showPopup(button){
            const loggedUserId = <?= json_encode($logged_user_id) ?>;
            const classId      = <?= json_encode($class_id) ?>;
            const userId       = button.getAttribute("data-student-id");
            const teacherId    = button.getAttribute("data-teacher-id");
            const popup        = document.getElementById("deletePopup");
            const confirmBtn   = popup.querySelector("a.btn-red");

            if(userId !== null) confirmBtn.href = `../../../../../private/php/sessions/delete-student.php?student=${encodeURIComponent(userId)}&user=${encodeURIComponent(loggedUserId)}&class=${encodeURIComponent(classId)}`;
            else if(teacherId !== null) confirmBtn.href = `../../../../../private/php/sessions/delete-teacher.php?teacher=${encodeURIComponent(teacherId)}&user=${encodeURIComponent(loggedUserId)}&class=${encodeURIComponent(classId)}`;

            popup.style.display = "flex";
        }

        function hidePopup(){
            document.getElementById("deletePopup").style.display = "none";
        }

        function showDeletePopup(){
            document.getElementById("deleteClassPopup").style.display = "flex";
        }

        function hideDeletePopup(){
            document.getElementById("deleteClassPopup").style.display = "none";
        }
    </script>
</body>
</html>