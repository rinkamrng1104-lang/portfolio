<?php
header("Access-Control-Allow-Origin: *"); // CORS対応
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "No data received"]);
    exit;
}

$name = isset($data['name']) ? trim($data['name']) : '';
$message = isset($data['message']) ? trim($data['message']) : '';

if (empty($name) || empty($message)) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}

// メール送信設定（任意、XAMPPのメール設定に依存）
$to = "your_email@example.com";  // 受信メールアドレス
$subject = "ポートフォリオサイトからのお問い合わせ";
$body = "Name: $name\nMessage:\n$message";
$headers = "From: no-reply@example.com\r\n" .
           "Reply-To: no-reply@example.com\r\n" .
           "X-Mailer: PHP/" . phpversion();

$mail_success = mail($to, $subject, $body, $headers);

if ($mail_success) {
    echo json_encode(["status" => "success", "message" => "Message sent"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to send message"]);
}
?>
