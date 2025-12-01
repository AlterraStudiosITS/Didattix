<?php

$config = require __DIR__ . '/config.php';
$db = $config['database'];

function getConnection(){
    global $db;
    $conn = mysqli_connect($db['host'], $db['user'], $db['pass'], $db['name']);
    if(!$conn) die("Connection failed: " . mysqli_connect_error());
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

function encrypt($data){
    return openssl_encrypt($data, 'AES-128-ECB', "BMe0MsNVN4");
}

function decrypt($data){
    return openssl_decrypt($data, 'AES-128-ECB', "BMe0MsNVN4");
}

function is_strong_password($password){
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
}

?>