<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    //Load Composer's autoloader (created by composer, not included with PHPMailer)
    require 'mailer/vendor/autoload.php';

    require_once "functions.php";

    createUsersTable();
    createClassesTable();
    createRelationStudentsClassesTable();
    createRelationTeachersClassesTable();
    createPresencesTable();
    createWarnTable();
    createGradesTable();

    if(!$conn = getConnection()) die("Connection failed: " . mysqli_connect_error());

    $username_admin = "ADMIN";
    $username_admin_enc = encrypt($username_admin);

    $stmt = $conn->prepare("SELECT id FROM users WHERE nome = ? LIMIT 1");
    $stmt->bind_param("s", $username_admin_enc);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows == 0){
        $url_address  = "admin.didattix.it";
        $password     = "admin123";
        $user_type    = "Amministratore";
        $linked_email = "codewithfil@gmail.com";
        $date         = date("Y-m-d H:i:s");

        $username_admin_enc = encrypt($username_admin);
        $password_hashed    = password_hash($password, PASSWORD_DEFAULT);
        $linked_email_enc   = encrypt($linked_email);
        $url_address_enc    = encrypt($url_address);
        $date_enc           = encrypt($date);

        $insert_stmt = $conn->prepare("INSERT INTO users (url_address, nome, password, user_type, linked_email, date) VALUES (?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("ssssss", $url_address_enc, $username_admin_enc, $password_hashed, $user_type, $linked_email_enc, $date_enc);

        if(!$insert_stmt->execute()) echo "Error creating ADMIN user: " . $insert_stmt->error;

        $insert_stmt->close();
    }

    $stmt->close();
    $conn->close();

    // --------------------------------------------------
    // ----------------- TABLES CREATION ----------------
    // --------------------------------------------------

    function createUsersTable(){
        $conn = getConnection();

        $sql = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                url_address VARCHAR(255),
                nome VARCHAR(255) NOT NULL,
                cognome VARCHAR(255),
                data_di_nascita VARCHAR(255),
                codice_fiscale VARCHAR(255),
                password VARCHAR(255) NOT NULL,
                user_type ENUM('Studente', 'Docente', 'Amministratore') NOT NULL,
                linked_email VARCHAR(255),
                date VARCHAR(255) NOT NULL,
                email_verification_token VARCHAR(255) DEFAULT NULL,
                email_verified TINYINT(1) DEFAULT 0,
                pending_email TEXT DEFAULT NULL,
                token_expiration DATETIME DEFAULT NULL,
                
                INDEX idx_url_address (url_address),
                INDEX idx_nome (nome),
                INDEX idx_cognome (cognome),
                INDEX idx_data_di_nascita (data_di_nascita),
                INDEX idx_user_type (user_type),
                INDEX idx_linked_email (linked_email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if(!$conn->query($sql)) die("Error creating `users` table: " . $conn->error);

        $conn->close();
    }
    function createClassesTable(){
        $conn = getConnection();

        $sql = "CREATE TABLE IF NOT EXISTS classi (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome_classe VARCHAR(255) NOT NULL,
                sezione VARCHAR(255) NOT NULL,
                anno_scolastico VARCHAR(255) NOT NULL,
                coordinatore_id INT DEFAULT NULL,

                UNIQUE(nome_classe, sezione, anno_scolastico),

                FOREIGN KEY (coordinatore_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if(!$conn->query($sql)) die("Error creating `classi` table: " . $conn->error);

        $conn->close();
    }
    function createRelationStudentsClassesTable(){
        $conn = getConnection();

        $sql = "CREATE TABLE IF NOT EXISTS studenti_classi (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                classe_id INT NOT NULL,

                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (classe_id) REFERENCES classi(id) ON DELETE CASCADE,

                UNIQUE(user_id, classe_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if(!$conn->query($sql)) die("Error creating `studenti_classi` table: " . $conn->error);

        $conn->close();
    }
    function createRelationTeachersClassesTable(){
        $conn = getConnection();

        $sql = "CREATE TABLE IF NOT EXISTS docenti_classi (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                classe_id INT NOT NULL,
                materia VARCHAR(255) NOT NULL,
                ruolo ENUM('titolare', 'supplente', 'sostegno', 'assistente', 'referente', 'tutor', 'correttore') NOT NULL,

                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (classe_id) REFERENCES classi(id) ON DELETE CASCADE

                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if(!$conn->query($sql)) die("Error creating `docenti_classi` table: " . $conn->error);

        $conn->close();
    }
    function createPresencesTable(){
        $conn = getConnection();

        $sql = "CREATE TABLE IF NOT EXISTS presenze (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                classe_id INT NOT NULL,
                teacher_id_signature INT NOT NULL,
                signature_description TEXT NOT NULL,
                data DATE NOT NULL,
                fascia_oraria VARCHAR(20) NOT NULL,
                stato ENUM('P','A') NOT NULL DEFAULT 'A',

                UNIQUE(user_id, classe_id, teacher_id_signature, data, fascia_oraria),

                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (classe_id) REFERENCES classi(id) ON DELETE CASCADE,
                FOREIGN KEY (teacher_id_signature) REFERENCES docenti_classi(user_id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if(!$conn->query($sql)) die("Error creating `presenze` table: " . $conn->error);

        $conn->close();
    }
    function createWarnTable(){
        $conn = getConnection();

        $sql = "CREATE TABLE IF NOT EXISTS ammonizioni (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                classe_id INT NOT NULL,
                teacher_id INT NOT NULL,
                descrizione TEXT NOT NULL,
                data DATE NOT NULL,

                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (classe_id) REFERENCES classi(id) ON DELETE CASCADE,
                FOREIGN KEY (teacher_id) REFERENCES docenti_classi(user_id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        if(!$conn->query($sql)) die("Error creating `ammonizioni` table: " . $conn->error);

        $conn->close();
    }
    function createGradesTable(){
        $conn = getConnection();

        $sql = "CREATE TABLE IF NOT EXISTS valutazioni (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,                     -- Studente
                classe_id INT NOT NULL,                   -- Classe di riferimento
                teacher_id INT NOT NULL,                  -- Docente che assegna la valutazione
                materia VARCHAR(255) NOT NULL,            -- Materia valutata
                voto DECIMAL(4,2) NOT NULL,               -- Valutazione (es: 1.25, 7.50, 10.00)
                descrizione TEXT DEFAULT NULL,            -- Eventuale nota o commento
                data DATE NOT NULL,                       -- Data della valutazione
                tipo ENUM('Orale', 'Scritto', 'Pratico', 'Altro') DEFAULT 'Altro', -- Tipo di prova

                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (classe_id) REFERENCES classi(id) ON DELETE CASCADE,
                FOREIGN KEY (teacher_id) REFERENCES docenti_classi(user_id) ON DELETE CASCADE,

                INDEX idx_user_id (user_id),
                INDEX idx_teacher_id (teacher_id),
                INDEX idx_classe_id (classe_id),
                INDEX idx_data (data)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        if(!$conn->query($sql)) die('Error creating `valutazioni` table: ' . $conn->error);

        $conn->close();
    }
    
    // ------------------------------------------------
    // ----------------- OTHER QUERIES ----------------
    // ------------------------------------------------

    function insertUser($conn, $url_address, $nome, $cognome, $data_nascita, $codice_fiscale, $tipo_utente, $password, $linked_email = null, $encryption_key = null){
        $date = date("Y-m-d H:i:s");

        $nome_enc = encrypt($nome);

        $cognome_enc        = null;
        $url_address_enc    = null;
        $data_nascita_enc   = null;
        $linked_email_enc   = null;
        $codice_fiscale_enc = null;
        $tipo_utente_enc    = null;

        if($cognome !== null)        $cognome_enc        = encrypt($cognome);
        if($url_address !== null)    $url_address_enc    = encrypt($url_address);
        if($data_nascita !== null)   $data_nascita_enc   = encrypt($data_nascita);
        if($linked_email !== null)   $linked_email_enc   = encrypt($linked_email);
        if($codice_fiscale !== null) $codice_fiscale_enc = encrypt($codice_fiscale);
        $date_enc        = encrypt($date);
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (url_address, nome, cognome, data_di_nascita, codice_fiscale, password, user_type, linked_email, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if($stmt === false) return ["success" => false, "error" => "Errore nella preparazione della query: " . $conn->error];

        $stmt->bind_param(
            "sssssssss",
            $url_address_enc,
            $nome_enc,
            $cognome_enc,
            $data_nascita_enc,
            $codice_fiscale_enc,
            $password_hashed,
            $tipo_utente,
            $linked_email_enc,
            $date_enc
        );

        if($stmt->execute()){
            $stmt->close();
            return ["success" => true];
        }else{
            $error = $stmt->error;
            $stmt->close();
            return ["success" => false, "error" => $error];
        }
    }

    function deleteUser($conn, int $user_id): array{
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        
        if($stmt === false) return ["success" => false, "error" => "Errore nella preparazione della query: " . $conn->error];

        $stmt->bind_param("i", $user_id);

        if($stmt->execute()){
            $affected_rows = $stmt->affected_rows;
            $stmt->close();

            if($affected_rows > 0) return ["success" => true];
            else return ["success" => false, "error" => "Nessun utente trovato con l'ID specificato."];
        }else{
            $error = $stmt->error;
            $stmt->close();
            return ["success" => false, "error" => $error];
        }
    }

    function urlExistsInDB($conn, string $url_address): bool{
        $stmt = $conn->prepare("SELECT id FROM users WHERE url_address = ? LIMIT 1");
        if(!$stmt) return false;
        $stmt->bind_param("s", $url_address);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    function updateEmail($conn, $user_id, $new_email): array{
        $email_enc = encrypt($new_email);
        $token = bin2hex(random_bytes(32));
        $expiration = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $check = $conn->prepare("SELECT token_expiration FROM users WHERE id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $check->bind_result($existing_expiration);
        $check->fetch();
        $check->close();

        if($existing_expiration && strtotime($existing_expiration) > time()){
            $remaining = strtotime($existing_expiration) - time();
            return [
                'success' => false,
                'error' => "⏳ È già in corso una verifica email. Riprova tra " . ceil($remaining / 60) . " minuti."
            ];
        }

        $stmt = $conn->prepare("
            UPDATE users 
            SET pending_email = ?, email_verification_token = ?, token_expiration = ?, email_verified = 0 
            WHERE id = ?
        ");
        $stmt->bind_param("sssi", $email_enc, $token, $expiration, $user_id);

        if(!$stmt->execute()) return ['success' => false, 'error' => "Errore SQL: " . $stmt->error];
        $stmt->close();

        $verify_link = "http://localhost/didattix/verify_email.php?token=" . urlencode($token);
        $subject = "Verifica la tua email";
        $htmlBody = "
            <h2>Verifica la tua email</h2>
            <p>Clicca sul link per confermare il tuo indirizzo email:</p>
            <p><a href='$verify_link'>$verify_link</a></p>
            <p>Il link scade tra 5 minuti.</p>
        ";
        $plainBody = "Verifica la tua email:\n$verify_link\nIl link scade tra 5 minuti.";

        $mail = new PHPMailer(true);
        $config = require __DIR__ . '/config.php';
        $mailCfg = $config['mail'];
        
        try{
            $mail->isSMTP();
            $mail->Host       = $mailCfg['host'];
            $mail->SMTPAuth   = $mailCfg['smtp_auth'];
            $mail->Username   = $mailCfg['username'];
            $mail->Password   = $mailCfg['password'];
            $mail->SMTPSecure = $mailCfg['encryption'];
            $mail->Port       = $mailCfg['port'];

            $mail->setFrom($mailCfg['from_email'], $mailCfg['from_name']);
            $mail->addAddress($new_email);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $plainBody;

            $mail->send();
            return ['success' => true];
        }catch(Exception $e){
            return ['success' => false, 'error' => "Errore nell'invio dell'email: {$mail->ErrorInfo}"];
        }
    }

    function classExists($name, $section){
        $conn = getConnection();

        $sql = "SELECT nome_classe, sezione FROM classi";
        $result = $conn->query($sql);

        if(!$result) return false;

        $name = strtolower($name);
        $section = strtolower($section);

        while($row = $result->fetch_assoc()){
            $db_name = strtolower(decrypt($row['nome_classe']));
            $db_section = strtolower(decrypt($row['sezione']));

            if($db_name === $name && $db_section === $section){
                $conn->close();
                return true;
            }
        }

        $conn->close();
        return false;
    }

    function createClass($nome, $indirizzo_studio, $anno_scolastico){
        $conn = getConnection();

        $stmt = $conn->prepare("INSERT INTO classi (nome_classe, sezione, anno_scolastico) VALUES (?, ?, ?)");
        if(!$stmt) return ['success' => false, 'error' => 'Errore nella preparazione della query.'];

        $nome_enc             = encrypt($nome);
        $indirizzo_studio_enc = encrypt($indirizzo_studio);
        $anno_scolastico_enc  = encrypt($anno_scolastico);

        $stmt->bind_param("sss", $nome_enc, $indirizzo_studio_enc, $anno_scolastico_enc);

        if($stmt->execute()){
            $stmt->close();
            $conn->close();
            return ['success' => true];
        }else{
            $error = $stmt->error;
            $stmt->close();
            $conn->close();
            return ['success' => false, 'error' => $error];
        }
    }

    function getStudentsClass($class_id){
        $conn = getConnection();

        $stmt = $conn->prepare("
            SELECT u.id, u.nome, u.cognome, u.data_di_nascita
            FROM users u
            JOIN studenti_classi sc ON u.id = sc.user_id
            WHERE sc.classe_id = ?
        ");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $students = [];
        while($row = $result->fetch_assoc()) {
            $row['nome'] = decrypt($row['nome']);
            $row['cognome'] = decrypt($row['cognome']);
            $row['data_di_nascita'] = decrypt($row['data_di_nascita']);
            $students[] = $row;
        }

        $stmt->close();
        $conn->close();

        usort($students, function($a, $b){
            $cmp = strcmp($a['cognome'], $b['cognome']);
            if($cmp === 0) return strcmp($a['nome'], $b['nome']);
            return $cmp;
        });

        return $students;
    }

    function getTeacherClasses($user_id){
        $conn = getConnection();

        $sql = "SELECT DISTINCT c.id, c.nome_classe, c.sezione, c.anno_scolastico
                FROM docenti_classi dc
                JOIN classi c ON dc.classe_id = c.id
                WHERE dc.user_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $classi = [];

        while($row = $result->fetch_assoc()) $classi[] = $row;

        $stmt->close();
        $conn->close();

        return $classi;
    }

    function getPresences($class_id, $formatted_date, $fascia_oraria){
        $conn = getConnection();

        $sql = "SELECT user_id, stato FROM presenze WHERE classe_id = ? AND data = ? AND fascia_oraria = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iss', $class_id, $formatted_date, $fascia_oraria);
        $stmt->execute();
        $result = $stmt->get_result();

        $presences = [];

        while($row = $result->fetch_assoc()) $presences[$row['user_id']] = $row['stato'];

        $stmt->close();
        $conn->close();

        return $presences;
    }

?>