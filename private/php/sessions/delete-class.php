<?php
session_start();
require_once("../autoload.php");

if(!isset($_SESSION['user_id'])){
    header("Location: ../../../index.php");
    exit;
}

$logged_user_id = $_SESSION['user_id'];

if(!isset($_GET['class']) || !is_numeric($_GET['class'])){
    header("Location: ../../../public/pages/dashboard/admin.php?error=not-found&user=" . urlencode($logged_user_id));
    exit;
}

$classe_id = intval($_GET['class']);

$conn = getConnection();

$conn->begin_transaction();

try{
    $stmt1 = $conn->prepare("DELETE FROM studenti_classi WHERE classe_id = ?");
    $stmt1->bind_param("i", $classe_id);
    $stmt1->execute();
    $stmt1->close();

    $stmt2 = $conn->prepare("DELETE FROM docenti_classi WHERE classe_id = ?");
    $stmt2->bind_param("i", $classe_id);
    $stmt2->execute();
    $stmt2->close();

    $stmt3 = $conn->prepare("DELETE FROM classi WHERE id = ?");
    $stmt3->bind_param("i", $classe_id);
    $stmt3->execute();
    $stmt3->close();

    $conn->commit();

    $conn->close();

    $query = http_build_query([
        'msg' => 'class-deleted',
        'user' => $logged_user_id
    ]);
    header("Location: ../../../public/pages/dashboard/admin/admin.php?$query");
    exit;

}catch(Exception $e){
    $conn->rollback();
    $conn->close();

    $query = http_build_query([
        'error' => 'delete-failed',
        'user' => $logged_user_id
    ]);
    header("Location: ../../../public/pages/dashboard/admin/admin.php?$query");
    exit;
}
?>