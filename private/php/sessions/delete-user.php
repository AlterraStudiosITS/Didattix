<?php
session_start();

require_once "../functions.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../../../../index.php");
    exit;
}

if(!$conn = getConnection()) die("Connessione fallita: " . mysqli_connect_error());

if(isset($_GET['user-edit'])){
    $logged_user_id = $_SESSION['user_id'];
    $id = $_GET['user-edit'];

    if(!filter_var($id, FILTER_VALIDATE_INT)) die("ID utente non valido.");

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    if(!$stmt) die("Errore nella preparazione della query: " . $conn->error);

    $stmt->bind_param("i", $id);
    $stmt->execute();

    if($stmt->affected_rows > 0) echo "Utente con ID $id eliminato con successo.";
    else echo "Nessun utente trovato con ID $id.";

    $stmt->close();
}else{
    header("Location: ../../../public/pages/dashboard/admin/admin.php?error=not-found&user=" . urldecode($logged_user_id));
    exit;
}

$conn->close();

header("Location: ../../../public/pages/dashboard/admin/admin.php?user=" . urldecode($logged_user_id));
?>