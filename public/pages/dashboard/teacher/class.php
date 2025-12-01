<?php
    require_once "../../../../private/php/autoload.php";
    session_start();

    if(!isset($_SESSION['user_id'])){
        header("Location: ../../../../index.php");
        exit;
    }

    $logged_user_id = $_SESSION['user_id'];

    if(!isset($_GET['user']) && !isset($_GET['class'])){
        header("Location: teacher.php?error=not-found&user=" . urlencode($logged_user_id));
        exit;
    }
    
    $conn                  = getConnection();
    $class_id              = $_GET['class'];
    $fasciaOraria          = $_GET['fascia_oraria'] ?? null;

    $selectedDate          = null;
    $docente_firmatario_id = null;
    $docenteFirmatario     = null;
    $presenzaFirmata_str   = null;
    $presenzaFirmata       = false;

    if(isset($_GET['date'])) $selectedDate = $_GET['date'];
    elseif(isset($_POST['date'])) $selectedDate = $_POST['date'];
    else $selectedDate = date('d-m-Y');

    $dateObj = DateTime::createFromFormat('d-m-Y', $selectedDate);
    $formattedDate = $dateObj ? $dateObj->format('Y-m-d') : null;

    if($fasciaOraria !== null){
        $stmt = $conn->prepare("SELECT u.id, u.nome, u.cognome
                                FROM presenze p
                                JOIN users u ON p.teacher_id_signature   = u.id
                                WHERE p.classe_id = ? AND p.data = ? AND p.fascia_oraria = ?
                                LIMIT 1");
        $stmt->bind_param("iss", $class_id, $formattedDate, $fasciaOraria);
        $stmt->execute();
        $result = $stmt->get_result();

        if($row = $result->fetch_assoc()){
            $presenzaFirmata = true;
            $docente_firmatario_id = $row['id'];
            $docenteFirmatario = decrypt($row['nome']) . ' ' . decrypt($row['cognome']);
        }

        $stmt->close();
    }

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
        header("Location: ../admin.php?error=not-found");
        exit;
    }
    $stmt->close();
    
    $ammonizioni = [];
    $stmt = $conn->prepare("SELECT id, user_id, descrizione FROM ammonizioni WHERE classe_id = ? AND data = ?");
    $stmt->bind_param("is", $class_id, $formattedDate);
    $stmt->execute();
    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()){
        $ammonizioni[$row['user_id']][] = [
            'id'          => $row['id'],
            'descrizione' => decrypt($row['descrizione'])
        ];
    }

    $stmt->close();

    $students = getStudentsClass($class_id);

    $presences = getPresences($class_id, $formattedDate, $fasciaOraria);
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

    <!-- Flatpickr API -->
    <link id="flatpickr-theme" rel="stylesheet" href="">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/it.js"></script>

    <script src="../../../../private/config/config.js"></script>
    <script src="../../../../private/js/app.js"></script>
</head>
<body>
    <header>
        <div class="logo" title="DiDattix">
            <img src="../../../../private/images/logo.png" alt="logo">
            <h2>Di<span class="danger">Dattix</span></h2>
        </div>
        <div class="navbar">
            <a href="teacher.php?user=<?= urlencode($logged_user_id) ?>" class="active">
                <span class="material-icons-sharp">home</span>
                <h3>Home</h3>
            </a>
            <a href="teacher.php?user=<?= urlencode($logged_user_id) ?>" onclick="timeTableAll()">
                <span class="material-icons-sharp">today</span>
                <h3>Orario</h3>
            </a> 
            <a href="teacher.php?user=<?= urlencode($logged_user_id) ?>">
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
            <!-- ===== OPZIONI ===== -->
            <h1>Opzioni</h1>
            <div class="subjects">
                <div class="eg">
                    <span class="material-icons-sharp">report</span>
                    <h3>Ammonizioni</h3>
                    <a href="warnings.php?date=<?= urlencode($selectedDate) ?>&fascia_oraria=<?= urlencode($fasciaOraria) ?>&user=<?= urlencode($logged_user_id) ?>&class=<?= urlencode($class_id) ?>">Clicca per vedere ⟶</a>
                </div>
                <div class="mth">
                    <span class="material-icons-sharp">grading</span>
                    <h3>Valutazioni</h3>
                    <a href="grades.php?date=<?= urlencode($selectedDate) ?>&fascia_oraria=<?= urlencode($fasciaOraria) ?>&user=<?= urlencode($logged_user_id) ?>&class=<?= urlencode($class_id) ?>">Clicca per vedere ⟶</a>
                </div>
            </div>
            <br><br>

            <!-- ===== STUDENTI ===== -->
            <h1>Studenti nella classe</h1>
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
                <?php if($presenzaFirmata): ?>
                    <p class="firmato">Firmato: <?= htmlspecialchars($docenteFirmatario) ?></p>
                <?php else: ?>
                    <button type="button" id="firma-btn" class="btn-discord btn-blue" style="border: none">❌ Non firmato</button>
                <?php endif; ?>
            </div>
            <div class="student-list" data-classid="<?= htmlspecialchars($_GET['class']) ?>">
                <?php if(empty($students)): ?>
                    <p>Non c'è nessuno studente assegnato a questa classe.</p>
                <?php else: ?>
                    <?php foreach($students as $index => $student): 
                        $userId = $student['id'];
                        $stato = $presences[$userId] ?? null;
                    ?>
                        <div class="student-card" data-userid="<?= $userId ?>">
                            <div class="student-details">
                                <div class="student-number"><?= $index + 1 ?></div>
                                <div class="student-info">
                                    <h3><?= htmlspecialchars($student['cognome'] . ' ' . $student['nome']) ?></h3>
                                    <p>Data di nascita: <?= htmlspecialchars($student['data_di_nascita']) ?></p>
                                </div>
                            </div>
                            <?php if(isset($ammonizioni[$userId])): ?>
                                <div class="notes">
                                    <?php foreach($ammonizioni[$userId] as $nota): ?>
                                        <p class="student-note">
                                            <a href="loading.php?date=<?= urlencode($selectedDate) ?>&fascia_oraria=<?= urlencode($fasciaOraria) ?>&user=<?= urlencode($logged_user_id) ?>&class=<?= urlencode($class_id) ?>&id=<?= urlencode($nota['id']) ?>">❌</a> NOTA: <?= htmlspecialchars($nota['descrizione']) ?>
                                        </p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="student-status">
                                <div class="status present <?= $stato === 'P' ? 'selected' : '' ?>">P</div>
                                <div class="status absent <?= $stato === 'A' ? 'selected' : '' ?>">A</div>
                                <div class="status options">
                                    <span class="material-icons-sharp more-options" onclick="toggleDropdown(event)">more_vert</span>
                                    <div class="dropdown-content">
                                        <a href="javascript:void(0);" onclick="showDelayPopup('<?= $userId ?>', '<?= htmlspecialchars($student['cognome'] . ' ' . $student['nome']) ?>')">
                                            <span class="d-status delay">R</span> Imposta ritardo
                                        </a>
                                        <a href="#"><span class="d-status exit">U</span> Imposta uscita</a>

                                        <?php if($stato === 'A'): ?>
                                            <a href="class.php?date=<?= urlencode($selectedDate) ?>&fascia_oraria=<?= urlencode($fasciaOraria) ?>&user=<?= urlencode($logged_user_id) ?>&class=<?= urlencode($class_id) ?>&error=student-absent">
                                                <span class="material-icons-sharp" style="color: red;">description</span> Ammonisci
                                            </a>
                                        <?php else: ?>
                                            <a href="warn.php?student=<?= urlencode($userId) ?>&class=<?= urlencode($class_id) ?>&date=<?= urlencode($formattedDate) ?>&fascia_oraria=<?= urlencode($fasciaOraria) ?>">
                                                <span class="material-icons-sharp" style="color: red;">description</span> Ammonisci
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
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
    <!-- Popup inserimento ritardo -->
    <div id="delay-popup" class="popup-overlay" style="display: none;">
        <div class="popup">
            <h3>Imposta ritardo</h3>
            <p id="student-name"></p>
            <label for="delay-time">Seleziona l'orario di arrivo:</label>
            <select id="delay-time">
                <option value="09:00">09:00</option>
                <option value="10:00">10:00</option>
                <option value="11:00">11:00</option>
                <option value="12:00">12:00</option>
                <option value="13:00">13:00</option>
                <option value="14:00">14:00</option>
            </select>
            <div class="popup-buttons">
                <button class="btn-blue" onclick="/*saveDelay()*/">Salva</button>
                <button class="btn-red" onclick="hidePopup()">Annulla</button>
            </div>
        </div>
    </div>

    <script>
        const loggedUserId = <?= json_encode($logged_user_id) ?>;
        const docenteFirmatarioId = <?= json_encode($docente_firmatario_id) ?>;
        const presenzaFirmata = <?= $presenzaFirmata ? 'true' : 'false' ?>;

        document.querySelectorAll('.student-status .status').forEach(statusDiv => {
            statusDiv.addEventListener('click', function(){
                if(this.classList.contains('options') || event.target.classList.contains('more-options')) return;

                if(presenzaFirmata && loggedUserId !== docenteFirmatarioId) {
                    alert("Non sei il docente firmatario dell'ora selezionata.");
                    return;
                }

                const status = this.textContent.trim(); //P/A
                const studentCard = this.closest('.student-card');
                const userId = studentCard.dataset.userid;
                const classListDiv = document.querySelector('.student-list');
                const classId = classListDiv.dataset.classid;
                const dateInput = document.getElementById('selected_date').value;
                const timeSlot = document.getElementById('fascia_oraria').value;

                if(!userId || !classId || !dateInput || !timeSlot) return;

                console.log("Loading presences for date:", dateInput);
                console.log("Loading presences for time:", timeSlot);

                fetch('../../../../private/php/sessions/save-presence.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        class_id: classId,
                        date: dateInput,
                        time_slot: timeSlot,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Risposta dal server:", data);
                    if(data.success){
                        const statusContainers = studentCard.querySelectorAll('.status');
                        statusContainers.forEach(el => el.classList.remove('selected'));
                        this.classList.add('selected');
                    }else alert('Error saving presence: ' + (data.message || data.error || 'Unknown error'));
                }).catch(err => alert('Request failed: ' + err));
            });
        });

        function loadPresences(){
            const classListDiv = document.querySelector('.student-list');
            const classId = classListDiv.dataset.classid;
            const dateInput = document.getElementById('selected_date').value;
            const timeSlot = document.getElementById('fascia_oraria').value;

            if(!classId || !dateInput) return;

            fetch(`../../../../private/php/sessions/get-presences.php?class_id=${classId}&date=${encodeURIComponent(dateInput)}&fascia_oraria=${encodeURIComponent(timeSlot)}`)
                .then(res => res.json())
                .then(data => {
                    if(data.success){
                        document.querySelectorAll('.student-card').forEach(studentCard => {
                            studentCard.querySelectorAll('.status.present, .status.absent').forEach(el => el.classList.remove('selected'));
                        });

                        Object.entries(data.presences).forEach(([userId, stato]) => {
                            const studentCard = document.querySelector(`.student-card[data-userid="${userId}"]`);
                            if(studentCard){
                                const presentDiv = studentCard.querySelector('.status.present');
                                const absentDiv = studentCard.querySelector('.status.absent');
                                if(stato === 'P') presentDiv.classList.add('selected');
                                else if(stato === 'A') absentDiv.classList.add('selected');
                            }
                        });
                    }else alert('Errore nel caricamento delle presenze: ' + data.message);
                }).catch(err => alert('Errore nella richiesta: ' + err));
        }

        function checkSignature(){
            const classListDiv = document.querySelector('.student-list');
            const classId = classListDiv.dataset.classid;
            const dateInput = document.getElementById('selected_date').value;
            const fascia = document.getElementById('fascia_oraria').value;
            const userId = "<?= urlencode($logged_user_id) ?>";

            if(!classId || !dateInput || !fascia) return;

            fetch(`../../../../private/php/sessions/check-signature.php?class_id=${classId}&data=${encodeURIComponent(dateInput)}&fascia_oraria=${encodeURIComponent(fascia)}&user_id=${userId}`)
                .then(res => res.json())
                .then(data => {
                    const firmaBtn = document.getElementById('firma-btn');
                    if(!firmaBtn) return;

                    if(!data.signed){
                        firmaBtn.disabled = false;
                        firmaBtn.textContent = "❌ Firma lezione";
                    }
                })
                .catch(err => console.error("Errore nel controllo firma:", err));
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadPresences();

            document.getElementById('selected_date').addEventListener('change', loadPresences);
        });

        document.addEventListener('DOMContentLoaded', () => {
            checkSignature();
            document.getElementById('selected_date').addEventListener('change', checkSignature);
            document.getElementById('fascia_oraria').addEventListener('change', checkSignature);
        });
    </script>
    <script>
        function toggleDropdown(event){
            const dropdown = event.target.nextElementSibling;
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
        window.onclick = function(event){
            if(!event.target.matches('.more-options')){
                const dropdowns = document.getElementsByClassName("dropdown-menu");
                for(let i = 0; i < dropdowns.length; i++){
                    dropdowns[i].style.display = "none";
                }
            }
        }
    </script>
    <script>
        const firmaBtn = document.getElementById('firma-btn');
        if(firmaBtn){
            firmaBtn.addEventListener('click', function () {
                const userId = "<?= urlencode($logged_user_id) ?>";
                const classId = "<?= urlencode($class_id) ?>";

                const dateEl = document.getElementById('selected_date');
                const fasciaEl = document.getElementById('fascia_oraria');

                if(!dateEl || !fasciaEl){
                    alert("Non è possibile firmare: data o fascia oraria non disponibili.");
                    return;
                }

                const date = dateEl.value;
                const fascia = fasciaEl.value;

                if(!date || !fascia){
                    alert("Seleziona una data e una fascia oraria prima di firmare.");
                    return;
                }

                const url = `sign.php?user=${userId}&class=${classId}&date=${encodeURIComponent(date)}&fascia_oraria=${encodeURIComponent(fascia)}`;
                window.location.href = url;
            });
        }

        document.querySelectorAll('#selected_date, #fascia_oraria').forEach(el => {
            el.addEventListener('change', () => {
                const date    = document.getElementById('selected_date').value;
                const fascia  = document.getElementById('fascia_oraria').value;
                const user    = "<?= urlencode($logged_user_id) ?>";
                const classId = "<?= urlencode($class_id) ?>";

                const url = new URL(window.location.href.split('?')[0]);
                url.searchParams.set('date', date);
                url.searchParams.set('fascia_oraria', fascia);
                url.searchParams.set('user', user);
                url.searchParams.set('class', classId);

                window.location.href = url.toString();
            });
        });
    </script>
    <script>
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

        function showDelayPopup(){
            document.getElementById("deleteClassPopup").style.display = "flex";
        }

        function hideDeletePopup(){
            document.getElementById("deleteClassPopup").style.display = "none";
        }
    </script>
</body>
</html>