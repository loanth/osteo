<?php
require 'vendor/autoload.php'; // Charger PHPMailer
use PHPMailer\PHPMailer\PHPMailer;

function configurerSMTP(PHPMailer $mail) {
    $mail->isSMTP();
    $mail->Host = 'smtp-relay.brevo.com';
    $mail->SMTPAuth = true;
    $mail->Username = '7efbd7001@smtp-brevo.com';
    $mail->Password = 'AhCkpKgODSQZqBfz';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
}
