<?php
// Файлы phpmailer
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';
require __DIR__ . '/phpmailer/Exception.php';

$title = "Запрос с сайта";
$file = $_FILES['file'];

$c = true;
// Формирование самого письма
$title = "Запрос на связь с сайта";
$name = trim((string)($_POST['name'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$message = trim((string)($_POST['message'] ?? ($_POST['comment'] ?? '')));

$fields = [
  'Имя' => $name,
  'Телефон' => $phone,
  'Сообщение' => $message,
];

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

// Настройки PHPMailer
$mail = new PHPMailer\PHPMailer\PHPMailer();

try {
  $mail->isSMTP();
  $mail->CharSet = "UTF-8";
  $mail->SMTPAuth   = true;

  // Настройки вашей почты , если что нужно менять почты для каждого сайта
  $mail->Host = 'smtp.mail.ru'; // SMTP сервера вашей почты
  $mail->Username   = 'ekb.design@mail.ru'; // Логин на почте , если использовать yandex то нужно указывать логин, а не всю почту
  $mail->Password   = 'H1PPRL0vLabP3t59tFhm'; // Пароль на почте
  $mail->SMTPSecure = 'ssl';
  $mail->Port       = 465;

  $mail->setFrom($mail->Username, 'Заявка с вашего сайта'); // Адрес самой почты и имя отправителя

  // Получатель письма
  $mail->addAddress('ekb.design@mail.ru');

  // Прикрипление файлов к письму
  if (!empty($file['name'][0])) {
    for ($ct = 0; $ct < count($file['tmp_name']); $ct++) {
      $uploadfile = tempnam(sys_get_temp_dir(), sha1($file['name'][$ct]));
      $filename = $file['name'][$ct];
      if (move_uploaded_file($file['tmp_name'][$ct], $uploadfile)) {
        $mail->addAttachment($uploadfile, $filename);
        $rfile[] = "Файл $filename прикреплён";
      } else {
        $rfile[] = "Не удалось прикрепить файл $filename";
      }
    }
  }

  // Отправка сообщения
  $mail->isHTML(true);
  $mail->Subject = $title;
  $mail->Body = $body;

  $mail->send();
} catch (Exception $e) {
  $status = "Сообщение не было отправлено. Причина ошибки: {$mail->ErrorInfo}";
}
