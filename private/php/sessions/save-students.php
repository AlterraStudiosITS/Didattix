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

    $selected_users = $_POST['users'] ?? [];

    if(!is_array($selected_users) || count($selected_users) === 0){
        header("Location: ../../../public/pages/dashboard/admin/classes/edit-class.php?error=no-student-selected&user=" . urlencode($logged_user_id) . "&class=" . urlencode($classe_id));
        exit;
    }

    $conn = getConnection();

    $selected_users = array_filter($selected_users, fn($id) => is_numeric($id));
    $selected_users = array_map('intval', $selected_users);

    $values = [];
    foreach($selected_users as $user_id) $values[] = "($user_id, $classe_id)";

    if(count($values) > 0){
        $sql = "INSERT IGNORE INTO studenti_classi (user_id, classe_id) VALUES " . implode(", ", $values);
        if(!$conn->query($sql)) die("Errore durante l'inserimento: " . $conn->error);
    }

    $conn->close();

    header("Location: ../../../public/pages/dashboard/admin/classes/edit-class.php?msg=added&user=" . urlencode($logged_user_id) . "&class=" . urlencode($classe_id));
    exit;
}else{
    header("Location: ../../../public/pages/dashboard/admin/classes/edit-class.php?user=" . urlencode($logged_user_id) . "&class=" . urlencode($classe_id));
    exit;
}
?>