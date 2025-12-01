<?php
require_once "./private/php/autoload.php";

$token = $_GET['token'] ?? '';
if(empty($token)){
    header("Location: /verification/verification_denied.php?token=" . $token);
    exit;
}

$conn = getConnection();

$stmt = $conn->prepare("
    SELECT id, pending_email, token_expiration 
    FROM users 
    WHERE email_verification_token = ?
");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->store_result();

if($stmt->num_rows === 1){
    $stmt->bind_result($user_id, $pending_email_enc, $token_expiration);
    $stmt->fetch();
    $stmt->close();

    if(strtotime($token_expiration) < time()){
        header("Location: /verification/verification_denied.php?token=" . $token);
        exit;
    }else{
        $update = $conn->prepare("
            UPDATE users 
            SET linked_email = ?, pending_email = NULL, email_verified = 1, email_verification_token = NULL 
            WHERE id = ?
        ");
        $update->bind_param("si", $pending_email_enc, $user_id);
        $update->execute();

        echo "✅ Email verificata con successo!";
    }
}else echo "❌ Token non valido o già usato.";

$conn->close();
?>