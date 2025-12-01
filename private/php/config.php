<?php

use PHPMailer\PHPMailer\PHPMailer;

return [

    // ---------------------------
    // DATABASE CONFIG
    // ---------------------------
    'database' => [
        'host' => 'localhost',
        'name' => 'didattix',
        'user' => 'root',
        'pass' => ''
    ],

    // ---------------------------
    // MAIL CONFIG
    // ---------------------------
    'mail' => [
        'host' => 'smtp.gmail.com',
        'smtp_auth' => true,
        'username' => 'alterrastudiosits@gmail.com',
        'password' => 'mrfk qiuw vtxb xrfu',
        'encryption' => PHPMailer::ENCRYPTION_STARTTLS,
        'port' => 587,
        'from_email' => 'noreply@didattix.it',
        'from_name' => 'Didattix'
    ]
];

?>