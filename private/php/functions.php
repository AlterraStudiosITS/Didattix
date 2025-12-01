<?php

    define("DB_NAME", "didattix");
    define("DB_USER", "root");
    define("DB_PASS", "");
    define("DB_HOST", "localhost");

    function getConnection(){
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
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