<?php
session_start();
require_once("../autoload.php");

if(!isset($_SESSION['user_id'])){
    header("Location: ../../../index.php");
    exit;
}

$logged_user_id = $_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(!isset($_GET['class']) || !is_numeric($_GET['class'])) die("Classe non specificata o non valida.");
    
    $classe_id = intval($_GET['class']);

    $teacher_id = $_POST['teacher_id'] ?? '';
    $subject    = $_POST['subject'] ?? '';
    $role       = $_POST['role'] ?? '';

    $errors = [];

    if($teacher_id === '' ) $errors[] = "Docente non valido.";
    if($subject === '') $errors[] = "Materia mancante.";
    if($role === '') $errors[] = "Ruolo mancante.";

    if(!empty($errors)){
        $query = http_build_query([
            'error' => 'validation',
            'user' => $logged_user_id,
            'class' => $classe_id
        ]);
        header("Location: ../../../public/pages/dashboard/admin/classes/edit-class.php?$query");
        exit;
    }

    $conn = getConnection();

    $subject_enc = encrypt($subject);

    $stmt_check = $conn->prepare("SELECT id FROM docenti_classi WHERE user_id = ? AND classe_id = ? AND materia = ?");
    $stmt_check->bind_param("iis", $teacher_id, $classe_id, $subject_enc);
    $stmt_check->execute();
    $stmt_check->store_result();

    if($stmt_check->num_rows > 0){
        $stmt_check->close();
        $conn->close();

        $query = http_build_query([
            'error' => 'already-exists',
            'user' => $logged_user_id,
            'class' => $classe_id
        ]);
        header("Location: ../../../public/pages/dashboard/admin/classes/edit-class.php?$query");
        exit;
    }

    $stmt_insert = $conn->prepare("INSERT INTO docenti_classi (user_id, classe_id, materia, ruolo) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("iiss", $teacher_id, $classe_id, $subject_enc, $role);

    if(!$stmt_insert->execute()){
        $stmt_insert->close();
        $conn->close();

        $query = http_build_query([
            'error' => 'insert-failed',
            'user' => $logged_user_id,
            'class' => $classe_id
        ]);
        header("Location: ../../../public/pages/dashboard/admin/classes/edit-class.php?$query");
        exit;
    }

    $stmt_insert->close();
    $conn->close();

    $query = http_build_query([
        'msg' => 'teacher-added',
        'user' => $logged_user_id,
        'class' => $classe_id
    ]);
    header("Location: ../../../public/pages/dashboard/admin/classes/edit-class.php?$query");
    exit;

}else{
    header("Location: ../../../public/pages/dashboard/admin/classes/edit-class.php?user=" . urlencode($logged_user_id) . "&class=" . urlencode($_GET['class'] ?? ''));
    exit;
}
?>