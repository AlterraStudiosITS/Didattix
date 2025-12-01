<?php
require_once '../autoload.php';

header('Content-Type: application/json');

$class_id = $_GET['class_id'] ?? null;
$date = $_GET['date'] ?? null;
$fascia = $_GET['fascia_oraria'] ?? null;
$user_id = $_GET['user_id'] ?? null;

if(!$class_id || !$date || !$fascia || !$user_id){
    echo json_encode(['success' => false, 'error' => 'Parametri mancanti']);
    exit;
}

$sql = "
    SELECT COUNT(*) AS total
    FROM presenze
    WHERE classe_id = ?
      AND data = ?
      AND fascia_oraria = ?
      AND teacher_id_signature = ?
";

$conn = getConnection();

$stmt = $conn->prepare($sql);
if(!$stmt){
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}

$stmt->bind_param("issi", $class_id, $date, $fascia, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$signed = $row['total'] > 0;

echo json_encode(['success' => true, 'signed' => $signed]);

$stmt->close();
$conn->close();