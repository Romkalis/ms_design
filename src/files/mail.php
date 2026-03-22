<?php
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';
require __DIR__ . '/phpmailer/Exception.php';

function respond(int $statusCode, string $message): void
{
  http_response_code($statusCode);
  header('Content-Type: text/plain; charset=UTF-8');
  echo $message;
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  respond(405, 'Method not allowed');
}

$origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
$referer = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));
$host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
if ($host !== '' && ($origin !== '' || $referer !== '')) {
  $originHost = $origin !== '' ? (parse_url($origin, PHP_URL_HOST) ?: '') : '';
  $refererHost = $referer !== '' ? (parse_url($referer, PHP_URL_HOST) ?: '') : '';
  if (($originHost !== '' && $originHost !== $host) || ($refererHost !== '' && $refererHost !== $host)) {
    respond(403, 'Forbidden');
  }
}

$ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
$now = time();
if ($ip !== '') {
  $rateLimitFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mailer_rl_' . hash('sha256', $ip) . '.json';
  $state = ['windowStart' => $now, 'count' => 0, 'lastSubmit' => 0];

  $handle = @fopen($rateLimitFile, 'c+');
  if ($handle !== false) {
    if (flock($handle, LOCK_EX)) {
      $raw = stream_get_contents($handle);
      $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
      if (is_array($decoded)) {
        $state = array_merge($state, $decoded);
      }

      $windowSeconds = 60;
      $maxPerWindow = 6;
      $minIntervalSeconds = 10;

      $windowStart = (int)($state['windowStart'] ?? $now);
      $count = (int)($state['count'] ?? 0);
      $lastSubmit = (int)($state['lastSubmit'] ?? 0);

      if ($now - $windowStart >= $windowSeconds) {
        $windowStart = $now;
        $count = 0;
      }

      if ($lastSubmit > 0 && ($now - $lastSubmit) < $minIntervalSeconds) {
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode(['windowStart' => $windowStart, 'count' => $count, 'lastSubmit' => $lastSubmit], JSON_UNESCAPED_UNICODE));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
        respond(429, 'Слишком часто. Попробуйте чуть позже.');
      }

      $count++;
      if ($count > $maxPerWindow) {
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode(['windowStart' => $windowStart, 'count' => $count, 'lastSubmit' => $lastSubmit], JSON_UNESCAPED_UNICODE));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
        respond(429, 'Слишком много запросов. Попробуйте позже.');
      }

      $state = ['windowStart' => $windowStart, 'count' => $count, 'lastSubmit' => $now];
      ftruncate($handle, 0);
      rewind($handle);
      fwrite($handle, json_encode($state, JSON_UNESCAPED_UNICODE));
      fflush($handle);
      flock($handle, LOCK_UN);
    }
    fclose($handle);
  }
}

$honeypot = trim((string)($_POST['company'] ?? ($_POST['website'] ?? '')));
if ($honeypot !== '') {
  respond(200, 'OK');
}

$formTs = (string)($_POST['form_ts'] ?? '');
if ($formTs !== '' && preg_match('/^\d{10,}$/', $formTs) === 1) {
  $tsMs = (int)$formTs;
  $ageSeconds = (int)floor((microtime(true) * 1000 - $tsMs) / 1000);
  if ($ageSeconds < 3) {
    respond(400, 'Не удалось отправить. Попробуйте ещё раз.');
  }
  if ($ageSeconds > 7200) {
    respond(400, 'Форма устарела. Обновите страницу и попробуйте ещё раз.');
  }
}

$name = trim((string)($_POST['name'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$message = trim((string)($_POST['message'] ?? ($_POST['comment'] ?? '')));

if ($name === '' || mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 50) {
  respond(400, 'Проверьте имя.');
}

$phoneDigits = preg_replace('/\D+/', '', $phone);
if ($phoneDigits === null || strlen($phoneDigits) !== 11 || $phoneDigits[0] !== '7') {
  respond(400, 'Проверьте номер телефона.');
}

if ($message !== '' && mb_strlen($message, 'UTF-8') > 2000) {
  respond(400, 'Сообщение слишком длинное.');
}

$messageLower = mb_strtolower($message, 'UTF-8');
if ($messageLower !== '' && (strpos($messageLower, 'http://') !== false || strpos($messageLower, 'https://') !== false || strpos($messageLower, 'www.') !== false || strpos($messageLower, 'href=') !== false)) {
  respond(200, 'OK');
}

$title = 'Запрос на связь с сайта';
$files = $_FILES['file'] ?? null;

$fields = [
  'Имя' => $name,
  'Телефон' => $phone,
  'Сообщение' => $message,
];

$c = true;
$body = '';
foreach ($fields as $label => $value) {
  if ($value === '') {
    continue;
  }
  $safeLabel = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  $safeValue = nl2br(htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
  $body .= "
    " . (($c = !$c) ? '<tr>' : '<tr style="background-color: #f8f8f8;">') . "
      <td style='padding: 10px; border: #e9e9e9 1px solid;'><b>$safeLabel</b></td>
      <td style='padding: 10px; border: #e9e9e9 1px solid;'>$safeValue</td>
    </tr>
    ";
}

$body = "<table style='width: 100%;'>$body</table>";

$mail = new PHPMailer\PHPMailer\PHPMailer();

try {
  $mail->isSMTP();
  $mail->CharSet = 'UTF-8';
  $mail->SMTPAuth = true;

  $mail->Host = 'smtp.mail.ru';
  $mail->Username = 'ekb.design@mail.ru';
  $mail->Password = 'H1PPRL0vLabP3t59tFhm';
  $mail->SMTPSecure = 'ssl';
  $mail->Port = 465;

  $mail->setFrom($mail->Username, 'Заявка с вашего сайта');
  $mail->addAddress('ekb.design@mail.ru');

  if (is_array($files) && isset($files['name']) && is_array($files['name']) && isset($files['tmp_name']) && is_array($files['tmp_name'])) {
    $maxFiles = 3;
    $maxFileSizeBytes = 5 * 1024 * 1024;
    $allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'doc', 'docx', 'txt'];

    $count = count($files['tmp_name']);
    $count = min($count, $maxFiles);

    for ($ct = 0; $ct < $count; $ct++) {
      $originalName = (string)($files['name'][$ct] ?? '');
      $tmpName = (string)($files['tmp_name'][$ct] ?? '');
      $error = (int)($files['error'][$ct] ?? UPLOAD_ERR_NO_FILE);
      $size = (int)($files['size'][$ct] ?? 0);

      if ($error === UPLOAD_ERR_NO_FILE || $originalName === '' || $tmpName === '') {
        continue;
      }
      if ($error !== UPLOAD_ERR_OK) {
        respond(400, 'Не удалось прикрепить файл.');
      }
      if ($size <= 0 || $size > $maxFileSizeBytes) {
        respond(400, 'Слишком большой файл.');
      }

      $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
      if ($ext === '' || !in_array($ext, $allowedExtensions, true)) {
        respond(400, 'Недопустимый формат файла.');
      }

      $uploadfile = tempnam(sys_get_temp_dir(), 'upl_');
      if ($uploadfile === false) {
        respond(500, 'Ошибка сервера.');
      }
      if (move_uploaded_file($tmpName, $uploadfile)) {
        $mail->addAttachment($uploadfile, $originalName);
      } else {
        respond(400, 'Не удалось прикрепить файл.');
      }
    }
  }

  $mail->isHTML(true);
  $mail->Subject = $title;
  $mail->Body = $body;

  $mail->send();
  respond(200, 'OK');
} catch (Throwable $e) {
  respond(500, 'Ошибка отправки. Попробуйте позже.');
}
