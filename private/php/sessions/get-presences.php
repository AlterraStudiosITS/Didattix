<?php
require_once '../autoload.php';

header('Content-Type: application/json');

$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : null;
$fasciaOraria = isset($_GET['fascia_oraria']) ? $_GET['fascia_oraria'] : null;

if(!$class_id || !$date || !$fasciaOraria){
    echo json_encode(['success' => false, 'message' => 'Parametri mancanti']);
    exit;
}

$dateObj = DateTime::createFromFormat('d-m-Y', $date);
if(!$dateObj){
    echo json_encode(['success' => false, 'message' => 'Data non valida']);
    exit;
}

$formattedDate = $dateObj->format('Y-m-d');

$conn = getConnection();

$stmt = $conn->prepare("SELECT user_id, stato FROM presenze WHERE classe_id = ? AND data = ? AND fascia_oraria = ?");
$stmt->bind_param('iss', $class_id, $formattedDate, $fasciaOraria);
$stmt->execute();
$result = $stmt->get_result();

$presences = [];
while($row = $result->fetch_assoc()) $presences[$row['user_id']] = $row['stato'];

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'presences' => $presences]);
?>