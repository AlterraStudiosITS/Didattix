<?php
session_start();

if(isset($_GET['student']) && isset($_GET['user']) && isset($_GET['class'])){
    $student_id      = intval($_GET['student']);
    $logged_user_id = $_GET['user'];
    $class_id       = $_GET['class'];

    require_once("../autoload.php");

    $conn = getConnection();

    $stmt = $conn->prepare("DELETE FROM studenti_classi WHERE user_id = ? AND classe_id = ?");
    $stmt->bind_param("ii", $student_id, $class_id);
    $stmt->execute();

    if($stmt->affected_rows > 0) header("Location: ../../../public/pages/dashboard/admin/classes/edit-class.php?msg=deleted&user=" . urlencode($logged_user_id) . "&class=" . urlencode($class_id));
    else header("Location: ../../../public/pages/dashboard/admin/classes/edit-class.php?msg=not_found&user=" . urlencode($logged_user_id) . "&class=" . urlencode($class_id));

    $stmt->close();
    $conn->close();
    exit;
}else{
    header("Location: ../../../public/pages/dashboard/admin/admin.php?error=invalid_id&user=" . urlencode($logged_user_id));
    exit;
}
?>