<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/libs/phpmailer/Exception.php';
require __DIR__ . '/libs/phpmailer/PHPMailer.php';
require __DIR__ . '/libs/phpmailer/SMTP.php';

// sendmail.php

function envValue($key, $default = '')
{
	$value = getenv($key);
	if ($value === false || $value === '') {
		if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
			$value = $_SERVER[$key];
		} elseif (isset($_ENV[$key]) && $_ENV[$key] !== '') {
			$value = $_ENV[$key];
		}
	}
	return $value === false || $value === '' ? $default : $value;
}

header('Content-Type: text/plain; charset=UTF-8');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$to = envValue('MAIL_TO', 'ekb.interrior.design@gmail.com');
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

	$smtpHost = envValue('SMTP_HOST', 'smtp.gmail.com');
	$smtpUser = envValue('SMTP_USER', 'ekb.interrior.design@gmail.com');
	$smtpPass = envValue('SMTP_PASS', 'cyoqnrdropozfnne');
	$smtpPort = (int) envValue('SMTP_PORT', '465');
	$smtpSecure = envValue('SMTP_SECURE', 'ssl');

	$smtpUser = trim($smtpUser);
	// $smtpPass = preg_replace('/\s+/', '', $smtpPass);

	$fromEmail = envValue('MAIL_FROM', $smtpUser ?: 'no-reply@example.com');
	$fromName = envValue('MAIL_FROM_NAME', 'Site Form');

	$simulateEnv = envValue('MAIL_SIMULATE', '');
	$simulate = $simulateEnv === '1';
	if ($simulateEnv === '') {
		$simulate = empty($smtpUser) || empty($smtpPass);
	}
	$debug = envValue('MAIL_DEBUG', '') === '1';
	if (
		!$debug &&
		isset($_GET['debug']) &&
		$_GET['debug'] === '1' &&
		isset($_SERVER['REMOTE_ADDR']) &&
		($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1')
	) {
		$debug = true;
	}

	$mail = new PHPMailer(true);

	try {
		if (!$simulate && (empty($smtpUser) || empty($smtpPass))) {
			http_response_code(500);
			echo 'Mail server is not configured.';
			exit();
		}

		$mail->isSMTP();
		$mail->Host = $smtpHost;
		$mail->SMTPAuth = true;
		$mail->Username = $smtpUser;
		$mail->Password = $smtpPass;
		$mail->SMTPSecure = $smtpSecure === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
		$mail->Port = $smtpPort;
		if ($debug) {
			$mail->SMTPDebug = SMTP::DEBUG_SERVER;
			$mail->Debugoutput = function ($str, $level) {
				error_log("SMTP[$level] $str");
			};
		}
		$mail->CharSet = 'UTF-8';

		//Recipients
		$mail->setFrom($fromEmail, $fromName);
		$mail->addAddress($to); //Add a recipient

		//Content
		$mail->isHTML(false); //Set email format to HTML
		$mail->Subject = $subject;
		$mail->Body = $message;

		if (!$simulate) {
			try {
				$mail->send();
				http_response_code(200);
				echo 'Message sent successfully.';
				exit();
			} catch (Exception $e) {
				$errorInfo = $mail->ErrorInfo;

				if ($smtpHost === 'smtp.gmail.com' && $smtpPort === 465 && ($smtpSecure === 'ssl' || $smtpSecure === '')) {
					$mailAlt = new PHPMailer(true);
					$mailAlt->isSMTP();
					$mailAlt->Host = $smtpHost;
					$mailAlt->SMTPAuth = true;
					$mailAlt->Username = $smtpUser;
					$mailAlt->Password = $smtpPass;
					$mailAlt->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
					$mailAlt->Port = 587;
					if ($debug) {
						$mailAlt->SMTPDebug = SMTP::DEBUG_SERVER;
						$mailAlt->Debugoutput = function ($str, $level) {
							error_log("SMTP_ALT[$level] $str");
						};
					}
					$mailAlt->CharSet = 'UTF-8';
					$mailAlt->setFrom($fromEmail, $fromName);
					$mailAlt->addAddress($to);
					$mailAlt->isHTML(false);
					$mailAlt->Subject = $subject;
					$mailAlt->Body = $message;

					$mailAlt->send();
					http_response_code(200);
					echo 'Message sent successfully.';
					exit();
				}

				throw new Exception($errorInfo ?: $e->getMessage(), 0, $e);
			}
		}

		http_response_code(200);
		echo 'Message sent successfully (Simulated).';
	} catch (Exception $e) {
		$errorInfo = $mail->ErrorInfo ?: $e->getMessage();
		error_log("Mail Error: {$errorInfo}");

		if ($simulate) {
			http_response_code(200);
			echo 'Message sent successfully (Simulated).';
			exit();
		}

		http_response_code(500);
		if ($debug && $errorInfo) {
			echo 'Mail send failed: ' . $errorInfo;
			exit();
		}
		echo 'Mail send failed.';
	}
} else {
	http_response_code(405);
	if (isset($_GET['debug']) && $_GET['debug'] === '1' && isset($_SERVER['REMOTE_ADDR']) && ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1')) {
		$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'UNKNOWN';
		$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		echo "Method not allowed: {$method}\n{$uri}\n";
		exit();
	}
	echo 'There was a problem with your submission, please try again.';
}
?>
