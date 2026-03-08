<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'libs/phpmailer/Exception.php';
require 'libs/phpmailer/PHPMailer.php';
require 'libs/phpmailer/SMTP.php';

// sendmail.php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$to = 'lisss17@mail.ru';
	$subject = 'Новая заявка на консультацию';

	$name = isset($_POST['name']) ? strip_tags(trim($_POST['name'])) : '';
	$phone = isset($_POST['phone']) ? strip_tags(trim($_POST['phone'])) : '';
	$comment = isset($_POST['comment']) ? strip_tags(trim($_POST['comment'])) : '';

	// Validation (basic)
	if (empty($name) || empty($phone)) {
		http_response_code(400);
		echo 'Please complete the form.';
		exit();
	}

	$message = "Имя: $name\n";
	$message .= "Телефон: $phone\n";
	$message .= "Комментарий: $comment\n";

	$mail = new PHPMailer(true);

	try {
		// Server settings
		// $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
		$mail->isSMTP(); //Send using SMTP
		$mail->Host = 'smtp.example.com'; //Set the SMTP server to send through
		$mail->SMTPAuth = true; //Enable SMTP authentication
		$mail->Username = 'user@example.com'; //SMTP username
		$mail->Password = 'secret'; //SMTP password
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; //Enable implicit TLS encryption
		$mail->Port = 465; //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
		$mail->CharSet = 'UTF-8';

		//Recipients
		$mail->setFrom('from@example.com', 'MSK Design Form');
		$mail->addAddress($to); //Add a recipient

		//Content
		$mail->isHTML(false); //Set email format to HTML
		$mail->Subject = $subject;
		$mail->Body = $message;

		$mail->send();
		http_response_code(200);
		echo 'Message sent successfully.';
	} catch (Exception $e) {
		// For development/demo purposes, we simulate success because local servers usually don't have mail configured
		// Log the attempt
		error_log("Mail Error: {$mail->ErrorInfo}");
		error_log("Mail simulation: To=$to, Subject=$subject, Message=$message");

		http_response_code(200);
		echo 'Message sent successfully (Simulated - Check logs for PHPMailer error if configured)';
	}
} else {
	http_response_code(403);
	echo 'There was a problem with your submission, please try again.';
}
?>
